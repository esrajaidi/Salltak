<?php

namespace App\Services;

use App\Models\DepositRule;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(private readonly DepositCalculator $depositCalculator) {}

    public function transition(Order $order, string $to, ?User $actor = null, ?string $note = null): void
    {
        if (!in_array($to, Order::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'حالة الطلب غير صالحة.']);
        }
        if ($to === 'delivered' && (float) $order->remaining_amount > 0.009) {
            throw ValidationException::withMessages(['status' => 'لا يمكن تسليم الطلب قبل سداد المبلغ المتبقي بالكامل.']);
        }
        if ($to === 'rejected' && trim((string) $note) === '') {
            throw ValidationException::withMessages(['reason' => 'سبب رفض الطلب مطلوب.']);
        }

        $from = $order->status;
        $payload = ['status' => $to];
        if ($to === 'rejected') $payload['rejection_reason'] = $note;
        if ($to === 'under_review' && !$order->reviewed_at) $payload['reviewed_at'] = now();
        if ($to === 'delivered') $payload['delivered_at'] = now();
        $order->update($payload);
        $order->histories()->create([
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    public function recalculateTotal(Order $order): float
    {
        $order->loadMissing('items');
        $total = 0.0;
        foreach ($order->items as $item) {
            if (in_array($item->review_status, ['unavailable', 'rejected'], true) || $item->customer_decision === 'reject') {
                $line = 0.0;
            } else {
                $unit = $item->reviewed_unit_price_lyd !== null ? (float) $item->reviewed_unit_price_lyd : (float) $item->unit_price_lyd;
                $line = round($unit * (int) $item->quantity, 2);
            }
            $item->update(['line_total_lyd' => $line]);
            $total += $line;
        }
        $total = round($total, 2);
        $order->update(['subtotal_lyd' => $total, 'total_lyd' => $total, 'remaining_amount' => max(0, $total - (float) $order->paid_amount)]);
        return $total;
    }

    public function applyPaymentTerms(Order $order, string $mode, ?float $value = null, ?string $note = null): float
    {
        $total = (float) $order->total_lyd;
        if ($mode === 'none') {
            $amount = 0.0;
            $type = 'none';
            $storedValue = 0.0;
        } elseif ($mode === 'auto') {
            $rules = DepositRule::query()->where('is_active', true)->orderBy('sort_order')->orderBy('min_total')->get();
            $amount = $this->depositCalculator->calculate($total, $rules);
            $type = 'auto';
            $storedValue = $amount;
        } elseif (in_array($mode, ['percentage', 'fixed'], true)) {
            $storedValue = max(0, (float) $value);
            $amount = $this->depositCalculator->calculate($total, [], ['type' => $mode, 'value' => $storedValue]);
            $type = $mode;
        } else {
            throw ValidationException::withMessages(['deposit_mode' => 'طريقة احتساب العربون غير صالحة.']);
        }

        $order->update([
            'deposit_required' => $amount > 0,
            'deposit_type' => $type,
            'deposit_value' => round($storedValue, 2),
            'deposit_amount' => round($amount, 2),
            'remaining_amount' => max(0, round($total - (float) $order->paid_amount, 2)),
            'payment_terms_note' => $note,
        ]);
        $order->refreshPaymentTotals();
        return round($amount, 2);
    }
}
