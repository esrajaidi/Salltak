<?php

namespace Tests\Feature;

use App\Services\CartImport\SheinUrlResolver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SheinOnelinkResolverTest extends TestCase
{
    private const SHARED = 'https://onelink.shein.com/55/example?shc=SHARE_A';

    public function test_one_link_falling_back_to_store_home_retains_the_original_share_link(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://m.shein.com/ar/'])]);

        $this->assertSame(self::SHARED, app(SheinUrlResolver::class)->resolve(self::SHARED));
    }

    public function test_one_link_following_a_real_shared_cart_landing_is_resolved(): void
    {
        $destination = 'https://m.shein.com/ar/cart/share/landing?shc=SHARE_A&group_id=123';
        Http::fake(['*' => Http::response('', 302, ['Location' => $destination])]);

        $this->assertSame($destination, app(SheinUrlResolver::class)->resolve(self::SHARED));
    }

    public function test_one_link_following_underscore_share_landing_is_resolved(): void
    {
        $destination = 'https://m.shein.com/share_landing?shc=SHARE_A';
        Http::fake(['*' => Http::response('', 302, ['Location' => $destination])]);

        $this->assertSame($destination, app(SheinUrlResolver::class)->resolve(self::SHARED));
    }

    public function test_mismatched_share_token_does_not_replace_original_link(): void
    {
        Http::fake(['*' => Http::response('', 302, [
            'Location' => 'https://m.shein.com/ar/cart/share/landing?shc=DIFFERENT&group_id=123',
        ])]);

        $this->assertSame(self::SHARED, app(SheinUrlResolver::class)->resolve(self::SHARED));
    }
}
