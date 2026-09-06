<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['currency', 'rate_to_lyd', 'is_active', 'updated_by'];

    protected function casts(): array
    {
        return ['rate_to_lyd' => 'decimal:4', 'is_active' => 'boolean'];
    }

    public static function rateFor(string $currency): float
    {
        if (strtoupper($currency) === 'LYD') {
            return 1.0;
        }

        return (float) static::query()
            ->where('currency', strtoupper($currency))
            ->where('is_active', true)
            ->value('rate_to_lyd');
    }
}
