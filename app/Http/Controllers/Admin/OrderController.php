<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function index(Request $request)
    {
        $query = Order::with(['user','assignee','cart.store'])->withCount('items')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('payment_status')) $query->where('payment_status', $request->string('payment_status')->toString());
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->integer('assigned_to'));
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($x) use ($q) {
                $x->where('number','like',"%{$q}%")
                    ->orWhereHas('user', fn($u)=>$u->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"));
            });
        }
        $managers = User::query()->whereIn('role',['admin','order_manager'])->where('is_active',true)->orderBy('name')->get();
        return view('admin.orders.index', ['orders'=>$query->paginate(20)->withQueryString(),'managers'=>$managers]);
    }

    public function show(Order $order)
    {
        $order->load(['user','assignee','cart.store','items.messages.user','messages.user','histories.user','payments.method','payments.verifier']);
        $managers = User::query()->whereIn('role',['admin','order_manager'])->where('is_active',true)->orderBy('name')->get();
        return view('admin.orders.show', compact('order','managers'));
    }

    public function assign(Request $request, Order $order)
    {
        $data = $request->validate(['assigned_to'=>['nullable','exists:users,id']]);
        if (!empty($data['assigned_to'])) {
            $valid = User::query()->whereKey($data['assigned_to'])->whereIn('role',['admin','order_manager'])->where('is_active',true)->exists();
            if (!$valid) return back()->withErrors(['assigned_to'=>'المستخدم المحدد ليس مسؤول طلبات فعال.']);
        }
        $order->update(['assigned_to'=>$data['assigned_to']??null]);
        $order->histories()->create(['user_id'=>$request->user()->id,'from_status'=>$order->status,'to_status'=>$order->status,'note'=>'تم تحديث المسؤول عن الطلب.']);
        return back()->with('success','تم تحديث المسؤول عن الطلب.');
    }

    public function reviewItem(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);
        $data = $request->validate([
            'review_status'=>['required',Rule::in(['approved','unavailable','price_changed','option_issue','rejected'])],
            'review_reason'=>['nullable','string','max:1500'],
            'reviewed_unit_price_lyd'=>['nullable','numeric','min:0','max:999999999'],
        ]);
        if ($data['review_status'] !== 'approved' && trim((string)($data['review_reason']??'')) === '') {
            return back()->withErrors(['review_reason'=>'اكتب سبب أو ملاحظة هذا المنتج.']);
        }
        $item->update([
            'review_status'=>$data['review_status'],
            'review_reason'=>$data['review_reason']??null,
            'reviewed_unit_price_lyd'=>$data['reviewed_unit_price_lyd']??null,
            'customer_decision'=>$data['review_status']==='approved'?null:$item->customer_decision,
        ]);
        if (in_array($data['review_status'],['unavailable','price_changed','option_issue'],true) && $order->status !== 'needs_customer_action') {
            $this->workflow->transition($order,'needs_customer_action',$request->user(),'يوجد منتج يحتاج رد العميل: '.$item->name);
        } elseif ($order->status === 'submitted') {
            $this->workflow->transition($order,'under_review',$request->user(),'بدأت مراجعة منتجات الطلب.');
        }
        $this->workflow->recalculateTotal($order);
        return back()->with('success','تم تحديث مراجعة المنتج.');
    }

    public function approve(Request $request, Order $order)
    {
        $data = $request->validate([
            'deposit_mode'=>['required',Rule::in(['auto','none','percentage','fixed'])],
            'deposit_value'=>['nullable','numeric','min:0','max:999999999'],
            'payment_terms_note'=>['nullable','string','max:1500'],
        ]);
        if ($order->items()->where('review_status','pending')->exists()) return back()->withErrors(['order'=>'راجع كل المنتجات قبل اعتماد الطلب.']);
        if ($order->items()->whereIn('review_status',['unavailable','price_changed','option_issue'])->whereNull('customer_decision')->exists()) {
            return back()->withErrors(['order'=>'هناك منتجات تحتاج رد العميل قبل الاعتماد.']);
        }
        if (in_array($data['deposit_mode'],['percentage','fixed'],true) && !isset($data['deposit_value'])) return back()->withErrors(['deposit_value'=>'قيمة العربون مطلوبة.']);

        $this->workflow->recalculateTotal($order);
        if ((float)$order->fresh()->total_lyd <= 0) return back()->withErrors(['order'=>'لا يمكن اعتماد طلب بإجمالي صفر.']);
        $order->refresh();
        $amount = $this->workflow->applyPaymentTerms($order,$data['deposit_mode'],isset($data['deposit_value'])?(float)$data['deposit_value']:null,$data['payment_terms_note']??null);
        $order->update(['approved_at'=>now(),'reviewed_at'=>$order->reviewed_at ?: now()]);
        if ($order->status !== 'approved') {
            $this->workflow->transition($order,'approved',$request->user(),'تم اعتماد المنتجات والسعر النهائي.');
        }
        $to = $amount > 0 ? 'awaiting_deposit' : 'awaiting_payment';
        $this->workflow->transition($order,$to,$request->user(),'شروط الدفع: العربون المطلوب '.number_format($amount,2).' د.ل');
        return back()->with('success','تم اعتماد الطلب وتحديد شروط الدفع.');
    }

    public function updatePaymentTerms(Request $request, Order $order)
    {
        $data = $request->validate([
            'deposit_mode'=>['required',Rule::in(['auto','none','percentage','fixed'])],
            'deposit_value'=>['nullable','numeric','min:0','max:999999999'],
            'reason'=>['required','string','max:1500'],
        ]);
        if (in_array($order->status,['purchasing','ordered','shipped','arrived_libya','ready_for_delivery','out_for_delivery','delivered'],true)) {
            return back()->withErrors(['deposit_mode'=>'لا يمكن تعديل شروط الدفع بعد بدء تنفيذ الطلب.']);
        }
        $amount = $this->workflow->applyPaymentTerms($order,$data['deposit_mode'],isset($data['deposit_value'])?(float)$data['deposit_value']:null,$data['reason']);
        $target = ((float)$order->paid_amount >= $amount && $amount > 0) ? 'deposit_paid' : ($amount > 0 ? 'awaiting_deposit' : 'awaiting_payment');
        $this->workflow->transition($order,$target,$request->user(),'تعديل شروط الدفع: '.$data['reason']);
        return back()->with('success','تم تعديل شروط الدفع.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate(['status'=>['required',Rule::in(Order::STATUSES)],'reason'=>['nullable','string','max:1500']]);
        if ($data['status']==='rejected' && trim((string)($data['reason']??''))==='') return back()->withErrors(['reason'=>'سبب الرفض مطلوب.']);
        $this->workflow->transition($order,$data['status'],$request->user(),$data['reason']??null);
        return back()->with('success','تم تحديث حالة الطلب.');
    }

    public function message(Request $request, Order $order)
    {
        $data=$request->validate(['message'=>['required','string','max:2000'],'order_item_id'=>['nullable','integer']]);
        if (!empty($data['order_item_id'])) abort_unless($order->items()->whereKey($data['order_item_id'])->exists(),422);
        $order->messages()->create(['user_id'=>$request->user()->id,'order_item_id'=>$data['order_item_id']??null,'message'=>$data['message']]);
        return back()->with('success','تم إرسال الرسالة للعميل.');
    }

    public function verifyPayment(Request $request, Order $order, Payment $payment)
    {
        abort_unless($payment->order_id === $order->id, 404);
        $data=$request->validate(['decision'=>['required',Rule::in(['verified','rejected'])],'reason'=>['nullable','string','max:1000']]);
        if ($data['decision']==='rejected' && trim((string)($data['reason']??''))==='') return back()->withErrors(['reason'=>'سبب رفض الدفعة مطلوب.']);
        $payment->update([
            'status'=>$data['decision'],
            'verified_by'=>$data['decision']==='verified'?$request->user()->id:null,
            'verified_at'=>$data['decision']==='verified'?now():null,
            'rejection_reason'=>$data['decision']==='rejected'?($data['reason']??null):null,
        ]);
        $order->refreshPaymentTotals();
        $order->refresh();
        if ($data['decision']==='verified' && $order->status==='awaiting_deposit' && (float)$order->paid_amount + 0.009 >= (float)$order->deposit_amount) {
            $this->workflow->transition($order,'deposit_paid',$request->user(),'تم التحقق من العربون ويمكن بدء الشراء.');
        }
        return back()->with('success',$data['decision']==='verified'?'تم اعتماد الدفعة.':'تم رفض الدفعة.');
    }
}
