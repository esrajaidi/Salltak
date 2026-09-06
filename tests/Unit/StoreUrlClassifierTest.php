<?php

namespace Tests\Unit;

use App\Services\StoreUrlClassifier;
use PHPUnit\Framework\TestCase;

class StoreUrlClassifierTest extends TestCase
{
    public function test_it_recognizes_shein_share_links(): void
    {
        $service = new StoreUrlClassifier();
        $this->assertTrue($service->isShein('https://onelink.shein.com/50/abc?shc=1'));
        $this->assertTrue($service->isShein('https://m.shein.com/ar/cart/share/landing?group_id=851956795&cart_share=1'));
        $this->assertFalse($service->isShein('https://example.com/cart'));
    }
}
