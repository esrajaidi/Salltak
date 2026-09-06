<?php

namespace App\Services;

class MoneyCalculator
{
    public function lineTotal(float $unitPrice, int $quantity): float
    {
        return round(max(0, $unitPrice) * max(1, $quantity), 2);
    }

    public function toLyd(float $amount, float $rate): float
    {
        return round(max(0, $amount) * max(0, $rate), 2);
    }
}
