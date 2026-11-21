<?php

namespace Tests\Unit;

use App\Services\CartImport\Browser\SheinBrowserImporter;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SheinResolvedShareRetryTest extends TestCase
{
    private function resolve(string $target): ?string
    {
        return (new ReflectionMethod(SheinBrowserImporter::class, 'resolvedSharedRetryUrl'))->invoke(
            new SheinBrowserImporter,
            'https://onelink.shein.com/61/example?shc=2_TestToken',
            $target
        );
    }

    public function test_mobile_identity_is_reused_on_www_without_changing_the_cart(): void
    {
        $result = $this->resolve('https://m.shein.com/ar/cart/share/landing?group_id=941308490&shc=2_TestToken&local_country=SA');
        $this->assertSame('https://www.shein.com/ar/cart/share/landing?group_id=941308490&shc=2_TestToken&local_country=AE&cart_share=1', $result);
    }

    public function test_unrelated_or_invalid_landings_cannot_supply_a_cart_identity(): void
    {
        foreach ([
            'https://m.shein.com/ar/cart/share/landing?group_id=941308490&shc=2_OtherToken',
            'https://example.com/ar/cart/share/landing?group_id=941308490&shc=2_TestToken',
            'https://m.shein.com/?group_id=941308490&shc=2_TestToken',
            'https://m.shein.com/ar/cart/share/landing?group_id[]=941308490&shc=2_TestToken',
            'https://m.shein.com/ar/cart/share/landing?group_id=invalid&shc=2_TestToken',
        ] as $url) {
            $this->assertNull($this->resolve($url));
        }
    }
}
