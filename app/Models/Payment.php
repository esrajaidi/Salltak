<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Payment extends Model
{
    protected $fillable=['number','order_id','payment_method_id','user_id','amount','fee_amount','status','transaction_ref','receipt_path','notes','verified_by','verified_at','rejection_reason','gateway_response'];
    protected function casts(): array { return ['amount'=>'decimal:2','fee_amount'=>'decimal:2','verified_at'=>'datetime','gateway_response'=>'array']; }
    protected static function booted(): void { static::creating(fn(Payment $p)=>$p->number ??= 'PAY-'.now()->format('ymd').'-'.strtoupper(Str::random(6))); }
    public function order(){ return $this->belongsTo(Order::class); }
    public function method(){ return $this->belongsTo(PaymentMethod::class,'payment_method_id'); }
    public function user(){ return $this->belongsTo(User::class); }
    public function verifier(){ return $this->belongsTo(User::class,'verified_by'); }
}
