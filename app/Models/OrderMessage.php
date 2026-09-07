<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderMessage extends Model
{
    protected $fillable=['order_id','order_item_id','user_id','message'];
    public function order(){ return $this->belongsTo(Order::class); }
    public function item(){ return $this->belongsTo(OrderItem::class,'order_item_id'); }
    public function user(){ return $this->belongsTo(User::class); }
}
