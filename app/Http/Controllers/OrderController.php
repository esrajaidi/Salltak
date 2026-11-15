<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\OrderWorkflowService;
use App\Services\OperationalEmailNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
        private readonly OperationalEmailNotifier $emailNotifier,
    ) {}

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with(['cart.store','assignee'])->withCount('items')->latest()->paginate(12);
        return view('orders.index', compact('orders'));
    }

    public function storeFromCart(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);

        $result = DB::transaction(function () use ($request, $cart) {
            // The cart row is the idempotency lock. Two rapid requests for the
            // same cart are serialized by MySQL and the second one reuses the
            // order created by the first request.
            $lockedCart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCart->user_id === $request->user()->id, 403);

            $existing = Order::query()->where('cart_id', $lockedCart->id)->first();
            if ($existing) {
                if ($lockedCart->status !== 'submitted') {
                    $lockedCart->update(['status' => 'submitted']);
                }
                return ['order' => $existing, 'created' => false];
            }

            $lockedCart->load('items');
            if ($lockedCart->status !== 'saved' || $lockedCart->items->isEmpty()) {
                return ['order' => null, 'created' => false];
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'cart_id' => $lockedCart->id,
                'status' => 'submitted',
                'payment_status' => 'unpaid',
                'subtotal_lyd' => $lockedCart->total_lyd,
                'total_lyd' => $lockedCart->total_lyd,
                'remaining_amount' => $lockedCart->total_lyd,
                'submitted_at' => now(),
            ]);

            foreach ($lockedCart->items as $item) {
                $unitLyd = round((float) $item->unit_price_original * (float) $lockedCart->exchange_rate, 2);
                $order->items()->create([
                    'cart_item_id'=>$item->id,'external_id'=>$item->external_id,'name'=>$item->name,
                    'product_url'=>$item->product_url,'image_url'=>$item->image_url,'variant'=>$item->variant,
                    'color'=>$item->color,'size'=>$item->size,'quantity'=>$item->quantity,
                    'unit_price_original'=>$item->unit_price_original,'unit_price_lyd'=>$unitLyd,
                    'line_total_lyd'=>round($unitLyd*(int)$item->quantity,2),'currency'=>$item->currency,
                ]);
            }

            $order->histories()->create([
                'user_id'=>$request->user()->id,'from_status'=>null,'to_status'=>'submitted',
                'event_type'=>'status_changed','visibility'=>'customer','note'=>'تم إرسال السلة كطلب للمراجعة.',
            ]);

            $lockedCart->update(['status' => 'submitted']);

            return ['order' => $order, 'created' => true];
        });

        /** @var \App\Models\Order|null $order */
        $order = $result['order'];
        if (! $order) {
            return back()->withErrors(['order' => 'لا يمكن إرسال هذه السلة كطلب في حالتها الحالية.']);
        }

        if (! $result['created']) {
            return redirect()->route('orders.show', $order)
                ->with('success', 'هذه السلة تم إرسالها مسبقًا كطلب. تم فتح الطلب المرتبط بها.');
        }

        $this->audit->log('order.submitted', 'إرسال طلب جديد', $request->user(), $order, 'تم تحويل السلة إلى طلب للمراجعة.', [], $order);
        $this->notifications->notifyBackoffice($order, 'order.submitted', 'طلب جديد '.$order->number, 'أرسل العميل طلبًا جديدًا ويحتاج المراجعة.', 'new-order');
        $this->emailNotifier->send('new_order', 'طلب جديد '.$order->number, 'أرسل '.$request->user()->name.' طلبًا جديدًا بقيمة '.number_format((float)$order->total_lyd, 2).' د.ل ويحتاج المراجعة.', route('admin.orders.show', $order));

        return redirect()->route('orders.show', $order)->with('success', 'تم إرسال الطلب للمراجعة بنجاح.');
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['cart.store','assignee','messages.user','histories.user','payments.method'])->loadCount('items');
        $itemsPage = $order->items()->with('messages.user')->orderBy('id')->paginate(12, ['*'], 'items_page')->withQueryString();
        $depositOutstanding = max(0, (float) $order->deposit_amount - (float) $order->paid_amount);
        $dueAmount = $depositOutstanding > 0 ? $depositOutstanding : (float) $order->remaining_amount;
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (PaymentMethod $method) => $method->canOfferForOrder($order, max(0.01, $dueAmount)))
            ->values();
        return view('orders.show', compact('order','paymentMethods','itemsPage'));
    }

    public function message(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $data = $request->validate(['message'=>['required','string','max:2000'],'order_item_id'=>['nullable','integer']]);
        if (!empty($data['order_item_id'])) {
            abort_unless($order->items()->whereKey($data['order_item_id'])->exists(), 422);
        }
        $order->messages()->create(['user_id'=>$request->user()->id,'order_item_id'=>$data['order_item_id']??null,'message'=>$data['message']]);
        $this->audit->log('order.customer_message', 'رسالة جديدة من العميل', $request->user(), $order, $data['message'], ['order_item_id'=>$data['order_item_id']??null], $order);
        $this->notifications->notifyBackoffice($order, 'order.customer_message', 'رسالة من العميل على '.$order->number, $data['message'], 'message');
        $this->emailNotifier->send('new_message', 'رسالة جديدة على '.$order->number, $request->user()->name.' كتب: '.$data['message'], route('admin.orders.show', $order));
        return back()->with('success', 'تم إرسال رسالتك للمسؤول.');
    }

    public function respondItem(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($order->user_id === $request->user()->id && $item->order_id === $order->id, 403);
        $data = $request->validate([
            'decision'=>['required', Rule::in(['accept','reject'])],
            'reply'=>['nullable','string','max:1500'],
        ]);
        $item->update(['customer_decision'=>$data['decision'],'customer_reply'=>$data['reply']??null]);
        $message = ($data['decision']==='accept'?'وافق العميل على التعديل.':'رفض العميل هذا المنتج/التعديل.').(!empty($data['reply'])?' '.$data['reply']:'');
        $order->messages()->create(['user_id'=>$request->user()->id,'order_item_id'=>$item->id,'message'=>$message]);
        $this->audit->log('order.item_customer_response', 'رد العميل على منتج', $request->user(), $item, $message, ['decision'=>$data['decision']], $order);
        $this->notifications->notifyBackoffice($order, 'order.item_customer_response', 'رد العميل على منتج في '.$order->number, $message, 'item');
        $this->emailNotifier->send('new_message', 'رد العميل على منتج في '.$order->number, $request->user()->name.' أرسل ردًا على أحد منتجات الطلب: '.$message, route('admin.orders.show', $order));
        if ($order->status === 'needs_customer_action') {
            $pendingReplies = $order->items()->whereIn('review_status',['unavailable','price_changed','option_issue'])
                ->whereNull('customer_decision')->exists();
            if (!$pendingReplies) $this->workflow->transition($order, 'under_review', $request->user(), 'اكتملت ردود العميل على ملاحظات المنتجات.');
        }
        return back()->with('success', 'تم حفظ ردك على المنتج.');
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $allowedStatuses = [
            'submitted', 'under_review', 'needs_customer_action', 'approved',
            'awaiting_deposit', 'awaiting_payment', 'deposit_paid',
        ];

        if (! in_array($order->status, $allowedStatuses, true)) {
            return back()->withErrors(['order' => 'لا يمكن إلغاء الطلب من الحساب في هذه المرحلة. يرجى التواصل مع المسؤول.']);
        }

        $paidAmount = max(0, (float) $order->paid_amount);
        $depositAmount = max(0, (float) $order->deposit_amount);
        $forfeitedDepositAmount = min($paidAmount, $depositAmount);
        $hasPaidDeposit = $forfeitedDepositAmount > 0.009;

        if ($paidAmount > 0.009 && $depositAmount <= 0.009) {
            return back()->withErrors(['order' => 'تم تسجيل دفعة على هذا الطلب، لذلك يجب التواصل مع المسؤول لإتمام الإلغاء.']);
        }

        if ($paidAmount > $depositAmount + 0.009) {
            return back()->withErrors(['order' => 'تم استلام مبلغ يتجاوز قيمة العربون. يرجى التواصل مع المسؤول لإتمام الإلغاء وتسوية المبلغ.']);
        }

        $rules = [
            'reason' => ['required', 'string', 'min:3', 'max:1500'],
            'cancellation_policy_acknowledged' => $hasPaidDeposit ? ['required', 'accepted'] : ['nullable'],
        ];
        $data = $request->validate($rules, [
            'reason.required' => 'يرجى كتابة سبب إلغاء الطلب.',
            'cancellation_policy_acknowledged.required' => 'يجب الإقرار بسياسة الإلغاء قبل إلغاء الطلب.',
            'cancellation_policy_acknowledged.accepted' => 'يجب الإقرار بسياسة الإلغاء قبل إلغاء الطلب.',
        ]);

        $reason = trim($data['reason']);
        $note = 'ألغى العميل الطلب. السبب: '.$reason;

        $this->workflow->transition(
            $order,
            'cancelled',
            $request->user(),
            $note,
            'customer',
            'status_changed',
            [
                'cancellation_reason' => $reason,
                'deposit_forfeited' => $hasPaidDeposit,
                'forfeited_deposit_amount' => round($forfeitedDepositAmount, 2),
                'cancellation_policy_acknowledged' => $hasPaidDeposit,
            ],
        );

        // لا يبقى على الطلب الملغي رصيد مستحق، مع الحفاظ على المبلغ المدفوع
        // في السجل المالي كما هو لأغراض المراجعة والتدقيق.
        $order->forceFill(['remaining_amount' => 0])->save();

        $message = $hasPaidDeposit
            ? 'تم إلغاء الطلب وتسجيل العربون المدفوع وفق سياسة الإلغاء.'
            : 'تم إلغاء الطلب بنجاح.';

        return back()->with('success', $message);
    }
}
