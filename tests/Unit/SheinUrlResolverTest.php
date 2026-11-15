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

    public function test_onelink_200_html_shareinfo_resolves_cart_without_http_redirect(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        $body = '<script>var shareInfo={"group_id":"876543210","shc":"2_RNBOXsQCBJ4"}</script>';
        Http::fake([$short => Http::response($body, 200, ['Content-Type' => 'text/html'])]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        $parts = parse_url($resolved);
        parse_str((string) ($parts['query'] ?? ''), $query);

        $this->assertSame('www.shein.com', $parts['host']);
        $this->assertSame('/ar/cart/share/landing', $parts['path']);
        $this->assertSame('876543210', $query['group_id']);
        $this->assertSame('2_RNBOXsQCBJ4', $query['shc']);
        $this->assertSame('AE', $query['local_country']);
        Http::assertSentCount(1);
    }

    public function test_onelink_without_group_id_is_not_falsely_resolved(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        Http::fake([$short => Http::response('<html>SHEIN homepage</html>', 200)]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        $this->assertSame($short, $resolved);
        Http::assertSentCount(1);
    }

    public function test_onelink_hidden_deeplink_data_resolves_cart_group(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        $data = rawurlencode(json_encode([
            'group_id' => '876543210',
            'shc' => '2_RNBOXsQCBJ4',
            'local_country' => 'SA',
        ]));
        $html = '<input type="hidden" id="deeplink" value="sheinlink://applink/share_receiver?data='.$data.'">';
        Http::fake([$short => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        $parts = parse_url($resolved);
        parse_str((string) ($parts['query'] ?? ''), $query);

        $this->assertSame('www.shein.com', $parts['host']);
        $this->assertSame('876543210', $query['group_id']);
        $this->assertSame('2_RNBOXsQCBJ4', $query['shc']);
        $this->assertSame('AE', $query['local_country']);
        Http::assertSentCount(1);
    }

    public function test_onelink_hidden_appjump_resolves_cart_group(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        $jump = 'https://api-shein.shein.com/h5/sharejump/appjump?source=onelink';
        $html = '<input id="url" value="'.htmlspecialchars($jump, ENT_QUOTES).'">';
        $jumpHtml = '<script>var shareInfo = {"id":876543210,"share_type":"cart","status":1};'. "\n".'</script>';
        Http::fake([
            $short => Http::response($html, 200),
            $jump => Http::response($jumpHtml, 200),
        ]);

        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        $parts = parse_url($resolved);
        parse_str((string) ($parts['query'] ?? ''), $query);

        $this->assertSame('www.shein.com', $parts['host']);
        $this->assertSame('876543210', $query['group_id']);
        $this->assertSame('2_RNBOXsQCBJ4', $query['shc']);
        Http::assertSentCount(2);
    }

    public function test_onelink_appjump_location_header_is_followed(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        $jump = 'https://api-shein.shein.com/h5/sharejump/appjump?source=onelink';
        Http::fake([
            $short => Http::response('', 302, ['Location' => $jump]),
            $jump => Http::response("<script>shareInfo={\"id\":\"876543210\",\"share_type\":\"cart\"};\n</script>", 200),
        ]);
        $resolved = (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short);
        parse_str((string) parse_url($resolved, PHP_URL_QUERY), $query);
        $this->assertSame('876543210', $query['group_id']);
        Http::assertSentCount(2);
    }

    public function test_onelink_does_not_follow_untrusted_hidden_urls(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        $html = '<input id="url" value="https://example.com/h5/sharejump/appjump?group_id=876543210">';
        Http::fake([$short => Http::response($html, 200)]);

        $this->assertSame(
            $short,
            (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short),
        );
        Http::assertSentCount(1);
    }

    public function test_non_cart_share_info_is_not_accepted(): void
    {
        $short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
        $jump = 'https://api-shein.shein.com/h5/sharejump/appjump?source=onelink';
        Http::fake([
            $short => Http::response('<input id="url" value="'.$jump.'">', 200),
            $jump => Http::response("<script>shareInfo={\"id\":\"876543210\",\"share_type\":\"product\"};\n</script>", 200),
        ]);

        $this->assertSame($short, (new SheinUrlResolver(new StoreUrlClassifier()))->resolve($short));
        Http::assertSentCount(2);
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
