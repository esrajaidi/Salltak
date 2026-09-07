<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! in_array($order->status, ['awaiting_deposit','awaiting_payment','deposit_paid','arrived_libya','awaiting_balance','ready_for_delivery','out_for_delivery'], true)) {
            return back()->withErrors(['payment' => 'الدفع غير متاح في حالة الطلب الحالية.']);
        }

        $data = $request->validate([
            'payment_method_id' => ['required','exists:payment_methods,id'],
            'amount' => ['required','numeric','min:1'],
            'transaction_ref' => ['nullable','string','max:190'],
            'receipt' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'notes' => ['nullable','string','max:1000'],
        ]);

        $method = PaymentMethod::query()->findOrFail($data['payment_method_id']);
        $amount = round((float) $data['amount'], 2);
        $remaining = (float) $order->remaining_amount;

        // Server-side guard: a disabled method can never be submitted by
        // manually changing the HTML form.
        if (! $method->canOfferForOrder($order, $amount)) {
            return back()->withErrors(['payment_method_id' => 'طريقة الدفع غير مفعلة أو غير متاحة لهذا المبلغ.']);
        }

        if ($amount > $remaining + 0.009) {
            return back()->withErrors(['amount' => 'المبلغ أكبر من الرصيد المتبقي على الطلب.']);
        }

        $depositOutstanding = max(0, (float) $order->deposit_amount - (float) $order->paid_amount);
        if ($order->status === 'awaiting_deposit' && $depositOutstanding > 0 && $amount + 0.009 < $depositOutstanding) {
            return back()->withErrors(['amount' => 'الحد الأدنى لهذه الدفعة هو قيمة العربون المتبقي: '.number_format($depositOutstanding, 2).' د.ل']);
        }

                $proofMode = $method->proofMode();
        $hasReference = ! empty($data['transaction_ref']);
        $hasReceipt = $request->hasFile('receipt');

        if ($proofMode === 'reference' && ! $hasReference) {
            return back()->withErrors(['transaction_ref' => 'رقم العملية مطلوب لهذه الطريقة.']);
        }
        if ($proofMode === 'receipt' && ! $hasReceipt) {
            return back()->withErrors(['receipt' => 'إيصال الدفع مطلوب لهذه الطريقة.']);
        }
        if ($proofMode === 'reference_or_receipt' && ! $hasReference && ! $hasReceipt) {
            return back()->withErrors(['receipt' => 'أدخل رقم العملية أو أرفق إيصال الدفع.']);
        }

        DB::transaction(function () use ($request, $order, $method, $data, $amount) {
            $receipt = $request->hasFile('receipt')
                ? $request->file('receipt')->store('payment-receipts', 'public')
                : null;

            $order->payments()->create([
                'payment_method_id' => $method->id,
                'user_id' => $request->user()->id,
                'amount' => $amount,
                'fee_amount' => $method->feeFor($amount),
                'status' => 'pending_verification',
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'receipt_path' => $receipt,
                'notes' => $data['notes'] ?? null,
            ]);
            $order->refreshPaymentTotals();
        });

        return back()->with('success', 'تم تسجيل الدفعة. سيتم اعتمادها بعد التحقق من المسؤول.');
    }
}
