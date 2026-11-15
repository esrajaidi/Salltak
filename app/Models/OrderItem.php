<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model
{
    protected $fillable = ['order_id','cart_item_id','external_id','name','product_url','image_url','variant','color','size','quantity','unit_price_original','unit_price_lyd','reviewed_unit_price_lyd','line_total_lyd','currency','review_status','review_reason','customer_decision','customer_reply'];
    protected function casts(): array { return ['quantity'=>'integer','unit_price_original'=>'decimal:2','unit_price_lyd'=>'decimal:2','reviewed_unit_price_lyd'=>'decimal:2','line_total_lyd'=>'decimal:2']; }
    public function order(){ return $this->belongsTo(Order::class); }
    public function messages(){ return $this->hasMany(OrderMessage::class); }
}
