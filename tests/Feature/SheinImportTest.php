<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SheinImportTest extends TestCase
{
    use RefreshDatabase;

    private const SHARE_URL = 'https://m.shein.com/ar/cart/share/landing?shc=2_RwCnM9DrOvA&group_id=851956795&local_country=AE&url_from=GM71035002695&cart_share=1';

    private function seedShein(): User
    {
        $user = User::factory()->create();
        Store::create([
            'name' => 'SHEIN', 'slug' => 'shein', 'domains' => ['shein.com', 'onelink.shein.com'],
            'currency' => 'USD', 'adapter' => 'shein', 'is_active' => true,
        ]);
        ExchangeRate::create(['currency' => 'USD', 'rate_to_lyd' => 7, 'is_active' => true]);
        ExchangeRate::create(['currency' => 'AED', 'rate_to_lyd' => 1.91, 'is_active' => true]);
        return $user;
    }

    public function test_shein_share_adapter_extracts_json_ld_product_when_exposed(): void
    {
        $user = $this->seedShein();
        Http::fake(['*' => Http::response('<html><script type="application/ld+json">{"@type":"Product","name":"Dress","sku":"S1","image":"https://img.test/a.jpg","offers":{"price":"12.50","priceCurrency":"USD"}}</script></html>', 200)]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => 'https://onelink.shein.com/50/example'])
            ->assertOk()->assertSee('Dress')->assertSee('12.5');
    }

    public function test_real_style_share_landing_extracts_multiple_cart_items_from_initial_state(): void
    {
        $user = $this->seedShein();
        $html = <<<'HTML'
<html><body><script>
window.__INITIAL_STATE__ = {
  "cartShareData": {
    "group_id": "851956795",
    "goods_list": [
      {
        "goods_id":"10001",
        "goods_sn":"sw1",
        "goods_name":"فستان نسائي",
        "goods_img":"//img.ltwebstatic.com/a.jpg",
        "product_url":"/ar/product-a-p-10001.html",
        "salePrice":{"amount":"49.90","usdAmount":"13.29","currency":"AED"},
        "cart_quantity":2,
        "color":"Black",
        "size":"M"
      },
      {
        "goods_id":"10002",
        "goods_sn":"sw2",
        "goods_name":"حقيبة يد",
        "goods_img":"//img.ltwebstatic.com/b.jpg",
        "product_url":"/ar/product-b-p-10002.html",
        "salePrice":{"amount":"25.00","usdAmount":"6.66","currency":"AED"},
        "quantity":1,
        "color":"Brown"
      }
    ]
  }
};
</script></body></html>
HTML;
        Http::fake(['*' => Http::response($html, 200)]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => self::SHARE_URL])
            ->assertOk()
            ->assertSee('فستان نسائي')
            ->assertSee('حقيبة يد')
            ->assertSee('13.29')
            ->assertSee('6.66')
            ->assertSee('USD')
            ->assertSee('Black')
            ->assertSee('M');
    }

    public function test_share_landing_uses_usd_even_when_local_country_is_ae_when_page_hides_items(): void
    {
        $user = $this->seedShein();
        Http::fake(['*' => Http::response('<html><body>app shell only</body></html>', 200)]);
        Process::fake(['*' => Process::result(output: json_encode(['ok'=>true,'status'=>'loaded','items'=>[],'payloads'=>[]]))]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => self::SHARE_URL])
            ->assertOk()
            ->assertSee('USD')
            ->assertSee('تشغيل JavaScript');
    }

    public function test_shein_429_falls_back_without_crashing_preview(): void
    {
        $user = $this->seedShein();
        Http::fake(['*' => Http::response('Too Many Requests', 429)]);
        Process::fake(['*' => Process::result(output: json_encode(['ok'=>false,'status'=>'failed','items'=>[],'payloads'=>[]]))]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => self::SHARE_URL])
            ->assertOk()
            ->assertSee('SHEIN منع الطلب المباشر')
            ->assertSee('USD');
    }

    public function test_share_landing_extracts_escaped_nested_app_state(): void
    {
        $user = $this->seedShein();
        $html = <<<'HTML'
<html><script>self.__next_f.push([1,"{\"cartShareData\":{\"goods_list\":[{\"quantity\":3,\"goods_info\":{\"goods_id\":\"30003\",\"goods_name\":\"حذاء نسائي\",\"goods_img\":{\"origin_image\":\"//img.ltwebstatic.com/c.jpg\"},\"sale_price\":{\"amount\":\"19.50\",\"currency\":\"AED\"},\"sku_sale_attr\":[{\"attr_name\":\"Color\",\"attr_value\":\"White\"},{\"attr_name\":\"Size\",\"attr_value\":\"39\"}]}}]}}"]);</script></html>
HTML;
        Http::fake(['*' => Http::response($html, 200)]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => self::SHARE_URL])
            ->assertOk()
            ->assertSee('حذاء نسائي')
            ->assertSee('White')
            ->assertSee('39')
            ->assertSee('5.19')
            ->assertSee('إجمالي المنتج')
            ->assertSee('30003');
    }
    public function test_playwright_browser_fallback_can_supply_real_cart_items(): void
    {
        $user = $this->seedShein();
        Http::fake(['*' => Http::response('<html><body>app shell only</body></html>', 200)]);
        Process::fake(['*' => Process::result(output: json_encode([
            'ok' => true,
            'status' => 'loaded',
            'final_url' => self::SHARE_URL,
            'items' => [[
                'external_id' => '99112233',
                'name' => 'طقم نسائي من SHEIN',
                'product_url' => 'https://m.shein.com/ar/example-p-99112233.html',
                'image_url' => 'https://img.ltwebstatic.com/example.jpg',
                'variant' => 'SKU-99112233-M',
                'color' => 'Black',
                'size' => 'M',
                'quantity' => 2,
                'unit_price_original' => 14.85,
                'currency' => 'USD',
            ]],
            'payloads' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => self::SHARE_URL])
            ->assertOk()
            ->assertSee('طقم نسائي من SHEIN')
            ->assertSee('Black')
            ->assertSee('M')
            ->assertSee('14.85')
            ->assertSee('99112233')
            ->assertSee('USD');

        Process::assertRan([(string) config('services.cart_import.shein_browser.node_binary', 'node'), base_path('scripts/shein-browser-import.mjs')]);
    }

    public function test_playwright_challenge_shows_mac_headed_mode_instruction(): void
    {
        $user = $this->seedShein();
        Http::fake(['*' => Http::response('<html><body>app shell only</body></html>', 200)]);
        Process::fake(['*' => Process::result(output: json_encode([
            'ok' => false, 'status' => 'challenge', 'message' => 'verify', 'items' => [], 'payloads' => [],
        ]))]);

        $this->actingAs($user)->post('/my-carts/analyze', ['source_url' => self::SHARE_URL])
            ->assertOk()
            ->assertSee('SHEIN طلب تحقق أمني')
            ->assertSee('Headless');
    }

}
