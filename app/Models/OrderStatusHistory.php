<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderStatusHistory extends Model
{
    protected $fillable=['order_id','user_id','from_status','to_status','event_type','visibility','note','metadata'];
    protected function casts(): array { return ['metadata'=>'array']; }
    public function order(){ return $this->belongsTo(Order::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function isInternal(): bool { return $this->visibility === 'internal'; }
}
