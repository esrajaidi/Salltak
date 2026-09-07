<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = [
        'submitted', 'under_review', 'needs_customer_action', 'approved', 'awaiting_deposit',
        'awaiting_payment', 'deposit_paid', 'purchasing', 'ordered', 'shipped', 'arrived_libya',
        'awaiting_balance', 'ready_for_delivery', 'out_for_delivery', 'delivered', 'rejected', 'cancelled',
    ];

    protected $fillable = [
        'number','user_id','cart_id','assigned_to','status','payment_status','subtotal_lyd','total_lyd',
        'deposit_required','deposit_type','deposit_value','deposit_amount','paid_amount','remaining_amount',
        'payment_terms_note','rejection_reason','submitted_at','reviewed_at','approved_at','delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'deposit_required' => 'boolean',
            'subtotal_lyd' => 'decimal:2', 'total_lyd' => 'decimal:2', 'deposit_value' => 'decimal:2',
            'deposit_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2',
            'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->number ??= 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function cart() { return $this->belongsTo(Cart::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function messages() { return $this->hasMany(OrderMessage::class); }
    public function histories() { return $this->hasMany(OrderStatusHistory::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function refreshPaymentTotals(): void
    {
        $paid = (float) $this->payments()->where('status', 'verified')->sum('amount');
        $remaining = max(0, (float) $this->total_lyd - $paid);
        $status = 'unpaid';
        if ($paid >= (float) $this->total_lyd && (float) $this->total_lyd > 0) $status = 'paid';
        elseif ($paid > 0 && $paid >= (float) $this->deposit_amount && (float) $this->deposit_amount > 0) $status = 'deposit_paid';
        elseif ($paid > 0) $status = 'partial';
        elseif ($this->payments()->whereIn('status', ['pending_verification','pending_gateway'])->exists()) $status = 'pending';

        $this->forceFill([
            'paid_amount' => round($paid, 2),
            'remaining_amount' => round($remaining, 2),
            'payment_status' => $status,
        ])->save();
    }
}
