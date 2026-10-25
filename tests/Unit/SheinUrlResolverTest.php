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
        Http::fake();
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

        $this->assertSame(str_replace('https://m.shein.com/', 'https://www.shein.com/', $final), $resolved);
        Http::assertSentCount(1);
    }

    public function test_onelink_cart_from_message_opens_www_even_if_shein_redirects_to_ar(): void
    {
        // Some SHEIN share messages contain a malformed "/?url_from="
        // separator after shc. Normalize it before resolving the short link.
        $short = 'https://onelink.shein.com/56/64kcvbmdsiau?shc=2_RkKGSegsOE6m/?url_from=GM71035002695';
        $repaired = 'https://onelink.shein.com/56/64kcvbmdsiau?shc=2_RkKGSegsOE6m&url_from=GM71035002695';
        $regional = 'https://ar.shein.com/cart/share/landing?group_id=851956795&local_country=SA&cart_share=1';

        Http::fake([
            $repaired => Http::response('', 302, ['Location' => $regional]),
        ]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        $parts = parse_url($resolved);
        parse_str((string) ($parts['query'] ?? ''), $query);

        $this->assertSame('https', $parts['scheme']);
        $this->assertSame('www.shein.com', $parts['host']);
        $this->assertSame('/cart/share/landing', $parts['path']);
        $this->assertSame('851956795', $query['group_id']);
        $this->assertSame('2_RkKGSegsOE6m', $query['shc']);
        $this->assertSame('GM71035002695', $query['url_from']);
        $this->assertSame('SA', $query['local_country']);
        Http::assertSentCount(1);
    }

    public function test_original_onelink_share_identity_takes_precedence_over_redirect_token(): void
    {
        $short = 'https://onelink.shein.com/56/test?shc=2_Original';
        $landing = 'https://ar.shein.com/ar/cart/share/landing?group_id=851956795&shc=2_FromShein&local_country=AE';
        Http::fake([
            $short => Http::response('', 302, ['Location' => $landing]),
        ]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        $this->assertSame(
            str_replace(['https://ar.shein.com', 'shc=2_FromShein'], ['https://www.shein.com', 'shc=2_Original'], $landing),
            $resolved,
        );
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
