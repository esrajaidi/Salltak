<?php

namespace Tests\Unit;

use App\Services\CartImport\SheinUrlResolver;
use App\Services\StoreUrlClassifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SheinUrlResolverTest extends TestCase
{
    public function test_direct_shein_share_link_is_left_unchanged(): void
    {
        $url = 'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE&cart_share=1';

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($url);

        $this->assertSame($url, $resolved);
        Http::assertNothingSent();
    }

    public function test_onelink_is_resolved_to_final_shein_share_url(): void
    {
        $short = 'https://onelink.shein.com/52/61qjncux1fzh?shc=2_R8AGnFXIuSR';
        $final = 'https://m.shein.com/ar/cart/share/landing?shc=2_R8AGnFXIuSR&group_id=851956795&local_country=AE&cart_share=1';

        Http::fake([
            $short => Http::response('', 302, ['Location' => $final]),
        ]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);

        $this->assertSame($final, $resolved);
        Http::assertSentCount(1);
    }

    public function test_onelink_rejects_redirects_outside_shein(): void
    {
        $short = 'https://onelink.shein.com/52/example?shc=abc';

        Http::fake([
            $short => Http::response('', 302, ['Location' => 'https://example.com/cart']),
        ]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);

        $this->assertSame($short, $resolved);
    }
}
