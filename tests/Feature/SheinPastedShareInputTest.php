<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SheinPastedShareInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_paste_full_shein_share_message_and_import_the_embedded_url(): void
    {
        $user = User::factory()->create();

        Store::create([
            'name' => 'SHEIN',
            'slug' => 'shein-paste-test',
            'domains' => ['shein.com', 'onelink.shein.com'],
            'currency' => 'USD',
            'adapter' => 'shein',
            'is_active' => true,
        ]);

        ExchangeRate::create([
            'currency' => 'USD',
            'rate_to_lyd' => 7,
            'is_active' => true,
        ]);

        $html = '<html><script type="application/ld+json">'.json_encode([
            '@type' => 'Product',
            'name' => 'منتج من رابط مشاركة SHEIN',
            'sku' => 'PASTE-1',
            'image' => 'https://img.test/paste.jpg',
            'offers' => ['price' => '9.85', 'priceCurrency' => 'USD'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'</script></html>';

        Http::fake(['*' => Http::response($html, 200)]);

        $sharedText = "لقد وجدت بعض المنتجات الرائعة في شي إن!\n"
            ."هذه المنتجات في سلة التسوق رائعة. أوصي بها بشدة للجميع!\n"
            ."\u{200B}https://onelink.shein.com/52/61qkc0qtpfme?shc=2_R8AUyWcowNI\u{200B}";

        $this->actingAs($user)
            ->post('/my-carts/analyze', ['source_url' => $sharedText])
            ->assertOk()
            ->assertSee('منتج من رابط مشاركة SHEIN')
            ->assertSee('9.85');
    }
}
