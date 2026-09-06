<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id', 'external_id', 'name', 'product_url', 'image_url', 'variant', 'color',
        'size', 'quantity', 'unit_price_original', 'line_total_original', 'currency', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_original' => 'decimal:2',
            'line_total_original' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
