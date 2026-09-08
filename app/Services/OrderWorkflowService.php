<?php

namespace App\Services;

use App\Models\DepositRule;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(
        private readonly DepositCalculator $depositCalculator,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
    ) {}

    public function transition(
        Order $order,
        string $to,
        ?User $actor = null,
        ?string $note = null,
        string $visibility = 'auto',
        string $eventType = 'status_changed',
        array $metadata = [],
    ): void {
        if (! in_array($to, Order::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'حالة الطلب غير صالحة.']);
        }
        $visibility = $this->resolveVisibility($to, $visibility, $eventType);
        if (! in_array($visibility, ['customer', 'internal'], true)) {
            throw ValidationException::withMessages(['visibility' => 'نوع ظهور الملاحظة غير صالح.']);
        }
        if ($to === 'delivered' && (float) $order->remaining_amount > 0.009) {
            throw ValidationException::withMessages(['status' => 'لا يمكن تسليم الطلب قبل سداد المبلغ المتبقي بالكامل.']);
        }
        if ($this->requiresReason($to) && trim((string) $note) === '') {
            throw ValidationException::withMessages(['reason' => 'اكتب سبب أو ملاحظة واضحة لهذه الحالة.']);
        }

        $from = $order->status;
        $payload = ['status' => $to];
        if ($to === 'rejected') $payload['rejection_reason'] = $note;
        if ($to === 'under_review' && ! $order->reviewed_at) $payload['reviewed_at'] = now();
        if ($to === 'delivered') $payload['delivered_at'] = now();
        $order->update($payload);

        $history = $order->histories()->create([
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'event_type' => $eventType,
            'visibility' => $visibility,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);

        $label = $this->statusLabel($to);
        $this->audit->log(
            'order.status_changed',
            'تغيير حالة الطلب إلى '.$label,
            $actor,
            $order,
            $note,
            array_merge(['from_status' => $from, 'to_status' => $to, 'visibility' => $visibility, 'history_id' => $history->id], $metadata),
            $order,
        );

        if ($actor?->role === 'customer') {
            $this->notifications->notifyBackoffice($order, 'order.customer_activity', 'تحديث من العميل على '.$order->number, $note ?: 'قام العميل بتحديث الطلب.', 'customer');
        } else {
            $customerBody = $visibility === 'customer' && $note ? $note : 'الحالة الحالية: '.$label;
            $this->notifications->notifyCustomer($order, 'order.status_changed', 'تحديث حالة الطلب '.$order->number, $customerBody, 'status', ['status' => $to]);
        }
    }

    public function requiresReason(string $status): bool
    {
        return in_array($status, ['rejected', 'cancelled', 'needs_customer_action'], true);
    }

    public function resolveVisibility(string $status, string $requested = 'auto', string $eventType = 'status_changed'): string
    {
        if ($this->requiresReason($status)) {
            return 'customer';
        }

        if ($requested !== 'auto') {
            return in_array($requested, ['customer', 'internal'], true) ? $requested : 'customer';
        }

        // تغيّر حالة الطلب هو جزء من رحلة العميل، لذلك يظهر له تلقائيًا.
        // الملاحظات التشغيلية المستقلة تبقى داخلية ما لم يحدد المسؤول غير ذلك.
        return $eventType === 'status_changed' ? 'customer' : 'internal';
    }

    public function addNote(Order $order, User $actor, string $note, string $visibility = 'internal', array $metadata = []): void
    {
        if (! in_array($visibility, ['customer', 'internal'], true)) {
            throw ValidationException::withMessages(['visibility' => 'نوع ظهور الملاحظة غير صالح.']);
        }
        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'الملاحظة مطلوبة.']);
        }

        $history = $order->histories()->create([
            'user_id' => $actor->id,
            'from_status' => $order->status,
            'to_status' => $order->status,
            'event_type' => 'note',
            'visibility' => $visibility,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);

        $this->audit->log(
            'order.note_added',
            $visibility === 'internal' ? 'إضافة ملاحظة داخلية' : 'إضافة ملاحظة للعميل',
            $actor,
            $order,
            $note,
            ['visibility' => $visibility, 'history_id' => $history->id] + $metadata,
            $order,
        );

        if ($visibility === 'customer' && $actor->id !== $order->user_id) {
            $this->notifications->notifyCustomer($order, 'order.note', 'ملاحظة جديدة على '.$order->number, $note, 'note');
        }
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

    public function syncAfterVerifiedPayment(Order $order, ?User $actor = null): void
    {
        $order->refreshPaymentTotals();
        $order->refresh();

        $prePurchaseStatuses = ['approved', 'awaiting_deposit', 'awaiting_payment', 'deposit_paid'];
        $isFullyPaid = $order->payment_status === 'paid'
            && (float) $order->total_lyd > 0
            && (float) $order->remaining_amount <= 0.009;

        if ($isFullyPaid && in_array($order->status, $prePurchaseStatuses, true)) {
            $this->transition(
                $order,
                'ready_for_purchase',
                $actor,
                'تم سداد الطلب بالكامل وأصبح جاهزًا للشراء.'
            );
            return;
        }

        if (
            $order->status === 'awaiting_deposit'
            && (float) $order->deposit_amount > 0
            && (float) $order->paid_amount + 0.009 >= (float) $order->deposit_amount
        ) {
            $this->transition($order, 'deposit_paid', $actor, 'تم التحقق من العربون ويمكن متابعة الدفع أو بدء إجراءات الشراء.');
        }
    }

    public function statusLabel(string $status): string
    {
        return [
            'submitted'=>'تم الإرسال','under_review'=>'تحت المراجعة','needs_customer_action'=>'يحتاج رد العميل',
            'approved'=>'معتمد','awaiting_deposit'=>'بانتظار العربون','awaiting_payment'=>'بانتظار الدفع',
            'deposit_paid'=>'العربون مدفوع','ready_for_purchase'=>'جاهز للشراء','purchasing'=>'جاري الشراء','ordered'=>'تم الطلب من المتجر',
            'shipped'=>'جاري الشحن','arrived_libya'=>'وصل ليبيا','awaiting_balance'=>'بانتظار باقي المبلغ',
            'ready_for_delivery'=>'جاهز للتسليم','out_for_delivery'=>'خرج للتسليم','delivered'=>'تم التسليم',
            'rejected'=>'مرفوض','cancelled'=>'ملغي',
        ][$status] ?? $status;
    }
}
