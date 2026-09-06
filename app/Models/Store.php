<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'domains', 'currency', 'logo_url', 'is_active', 'adapter'];

    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
}
