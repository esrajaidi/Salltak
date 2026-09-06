<?php

namespace Tests\Unit;

use App\Services\MoneyCalculator;
use PHPUnit\Framework\TestCase;

class MoneyCalculatorTest extends TestCase
{
    public function test_it_calculates_line_total(): void
    {
        $money = new MoneyCalculator();
        $this->assertSame(37.5, $money->lineTotal(12.5, 3));
    }

    public function test_it_converts_to_lyd(): void
    {
        $money = new MoneyCalculator();
        $this->assertSame(70.0, $money->toLyd(10, 7));
    }
}
