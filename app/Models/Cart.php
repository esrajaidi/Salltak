<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'user_id', 'store_id', 'source_url', 'source_host', 'source_currency',
        'exchange_rate', 'subtotal_original', 'total_lyd', 'status', 'import_status',
        'import_message', 'import_meta',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
            'subtotal_original' => 'decimal:2',
            'total_lyd' => 'decimal:2',
            'import_meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cart $cart) {
            $cart->number ??= 'CRT-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
