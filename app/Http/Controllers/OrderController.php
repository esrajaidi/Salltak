<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with(['cart.store','assignee'])->withCount('items')->latest()->paginate(12);
        return view('orders.index', compact('orders'));
    }

    public function storeFromCart(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        $cart->load('items');
        if ($cart->status === 'cancelled' || $cart->items->isEmpty()) {
            return back()->withErrors(['order' => 'لا يمكن طلب هذه السلة.']);
        }
        $existing = $request->user()->orders()->where('cart_id', $cart->id)
            ->whereNotIn('status', ['rejected','cancelled','delivered'])->latest()->first();
        if ($existing) return redirect()->route('orders.show', $existing)->with('success', 'هذه السلة لديها طلب نشط بالفعل.');

        $order = DB::transaction(function () use ($request, $cart) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'cart_id' => $cart->id,
                'status' => 'submitted',
                'payment_status' => 'unpaid',
                'subtotal_lyd' => $cart->total_lyd,
                'total_lyd' => $cart->total_lyd,
                'remaining_amount' => $cart->total_lyd,
                'submitted_at' => now(),
            ]);
            foreach ($cart->items as $item) {
                $unitLyd = round((float) $item->unit_price_original * (float) $cart->exchange_rate, 2);
                $order->items()->create([
                    'cart_item_id'=>$item->id,'external_id'=>$item->external_id,'name'=>$item->name,
                    'product_url'=>$item->product_url,'image_url'=>$item->image_url,'variant'=>$item->variant,
                    'color'=>$item->color,'size'=>$item->size,'quantity'=>$item->quantity,
                    'unit_price_original'=>$item->unit_price_original,'unit_price_lyd'=>$unitLyd,
                    'line_total_lyd'=>round($unitLyd*(int)$item->quantity,2),'currency'=>$item->currency,
                ]);
            }
            $order->histories()->create(['user_id'=>$request->user()->id,'from_status'=>null,'to_status'=>'submitted','note'=>'تم إرسال السلة كطلب للمراجعة.']);
            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'تم إرسال الطلب للمراجعة بنجاح.');
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['cart.store','assignee','items.messages.user','messages.user','histories.user','payments.method']);
        $depositOutstanding = max(0, (float) $order->deposit_amount - (float) $order->paid_amount);
        $dueAmount = $depositOutstanding > 0 ? $depositOutstanding : (float) $order->remaining_amount;
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (PaymentMethod $method) => $method->canOfferForOrder($order, max(0.01, $dueAmount)))
            ->values();
        return view('orders.show', compact('order','paymentMethods'));
    }

    public function message(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $data = $request->validate(['message'=>['required','string','max:2000'],'order_item_id'=>['nullable','integer']]);
        if (!empty($data['order_item_id'])) {
            abort_unless($order->items()->whereKey($data['order_item_id'])->exists(), 422);
        }
        $order->messages()->create(['user_id'=>$request->user()->id,'order_item_id'=>$data['order_item_id']??null,'message'=>$data['message']]);
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
        $order->messages()->create([
            'user_id'=>$request->user()->id,'order_item_id'=>$item->id,
            'message'=>($data['decision']==='accept'?'وافق العميل على التعديل.':'رفض العميل هذا المنتج/التعديل.').(!empty($data['reply'])?' '.$data['reply']:''),
        ]);
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
        if ((float)$order->paid_amount > 0 || !in_array($order->status,['submitted','under_review','needs_customer_action','approved','awaiting_deposit','awaiting_payment'],true)) {
            return back()->withErrors(['order'=>'لا يمكن إلغاء الطلب من الحساب في هذه المرحلة. تواصل مع المسؤول.']);
        }
        $this->workflow->transition($order, 'cancelled', $request->user(), 'ألغى العميل الطلب.');
        return back()->with('success','تم إلغاء الطلب.');
    }
}
