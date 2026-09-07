<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DepositRule extends Model
{
    protected $fillable=['name','min_total','max_total','type','value','is_active','sort_order'];
    protected function casts(): array { return ['min_total'=>'decimal:2','max_total'=>'decimal:2','value'=>'decimal:2','is_active'=>'boolean']; }
}
