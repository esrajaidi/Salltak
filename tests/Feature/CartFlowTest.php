<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_save_cart_and_conversion_is_calculated(): void
    {
        $user = User::factory()->create();
        $store = Store::create(['name'=>'SHEIN','slug'=>'shein','domains'=>['shein.com','onelink.shein.com'],'currency'=>'USD','adapter'=>'shein','is_active'=>true]);
        ExchangeRate::create(['currency'=>'USD','rate_to_lyd'=>7,'is_active'=>true]);

        $token = str_repeat('a', 48);
        $session = [
            'cart_import_preview.'.$token => [
                'user_id'=>$user->id,
                'source_url'=>'https://onelink.shein.com/50/example',
                'store_id'=>$store->id,
                'source_currency'=>'USD',
                'exchange_rate'=>7,
                'import_status'=>'success',
                'import_message'=>'ok',
                'created_at'=>now()->timestamp,
                'items'=>[
                    ['_key'=>'item-a','name'=>'Product A','quantity'=>1,'unit_price_original'=>10],
                    ['_key'=>'item-b','name'=>'Product B','quantity'=>1,'unit_price_original'=>5],
                ],
            ],
        ];

        $response = $this->actingAs($user)->withSession($session)->post('/my-carts', [
            'preview_token'=>$token,
            'items'=>[
                ['key'=>'item-a','quantity'=>2],
                ['key'=>'item-b','quantity'=>1],
            ],
        ]);

        $cart = $user->carts()->firstOrFail();
        $response->assertRedirect(route('carts.show',$cart));
        $this->assertSame('25.00', $cart->subtotal_original);
        $this->assertSame('175.00', $cart->total_lyd);
        $this->assertCount(2, $cart->items);
    }

    public function test_customer_can_save_and_order_item_with_long_shein_name(): void
    {
        $user = User::factory()->create();
        $store = Store::create(['name'=>'SHEIN','slug'=>'shein','domains'=>['shein.com'],'currency'=>'USD','adapter'=>'shein','is_active'=>true]);
        ExchangeRate::create(['currency'=>'USD','rate_to_lyd'=>7,'is_active'=>true]);
        $longName = str_repeat('منتج شي إن طويل للاختبار ', 18);

        $token = str_repeat('b', 48);
        $session = [
            'cart_import_preview.'.$token => [
                'user_id'=>$user->id,
                'source_url'=>'https://m.shein.com/ar/cart/share/landing?group_id=123',
                'store_id'=>$store->id,
                'source_currency'=>'USD',
                'exchange_rate'=>7,
                'import_status'=>'success',
                'import_message'=>'ok',
                'created_at'=>now()->timestamp,
                'items'=>[
                    ['_key'=>'long-item','external_id'=>'415154902','name'=>$longName,'quantity'=>1,'unit_price_original'=>3.97],
                ],
            ],
        ];

        $response = $this->actingAs($user)->withSession($session)->post('/my-carts', [
            'preview_token'=>$token,
            'items'=>[['key'=>'long-item','quantity'=>1]],
        ]);

        $response->assertSessionHasNoErrors();
        $cart = $user->carts()->latest('id')->firstOrFail();
        $this->assertSame($longName, $cart->items()->firstOrFail()->name);

        $this->actingAs($user)->post(route('orders.from-cart', $cart))->assertRedirect();
        $this->assertDatabaseHas('order_items', ['name'=>$longName]);
    }

    public function test_customer_cannot_tamper_with_imported_price(): void
    {
        $user = User::factory()->create();
        $store = Store::create(['name'=>'SHEIN','slug'=>'shein','domains'=>['shein.com'],'currency'=>'USD','adapter'=>'shein','is_active'=>true]);
        ExchangeRate::create(['currency'=>'USD','rate_to_lyd'=>7,'is_active'=>true]);
        $token = str_repeat('c', 48);

        $response = $this->actingAs($user)->withSession([
            'cart_import_preview.'.$token => [
                'user_id'=>$user->id,'source_url'=>'https://m.shein.com/ar/cart/share/landing?group_id=456',
                'store_id'=>$store->id,'source_currency'=>'USD','exchange_rate'=>7,'import_status'=>'success','import_message'=>'ok',
                'created_at'=>now()->timestamp,
                'items'=>[['_key'=>'locked-price','name'=>'Locked Product','quantity'=>1,'unit_price_original'=>19.95]],
            ],
        ])->post('/my-carts', [
            'preview_token'=>$token,
            'items'=>[['key'=>'locked-price','quantity'=>2,'unit_price_original'=>0.01]],
        ]);

        $response->assertSessionHasNoErrors();
        $item = $user->carts()->firstOrFail()->items()->firstOrFail();
        $this->assertSame('19.95', $item->unit_price_original);
        $this->assertSame('39.90', $item->line_total_original);
    }

    public function test_user_cannot_open_another_users_cart(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $cart = $owner->carts()->create([
            'source_url'=>'https://example.com/cart','source_currency'=>'USD','exchange_rate'=>1,
            'subtotal_original'=>0,'total_lyd'=>0,'status'=>'saved','import_status'=>'needs_review',
        ]);
        $this->actingAs($other)->get(route('carts.show',$cart))->assertForbidden();
    }

    public function test_shein_phone_checkout_prices_are_saved_separately_and_cannot_change_order_amount(): void
    {
        $user = User::factory()->create();
        $store = Store::create([
            'name' => 'SHEIN', 'slug' => 'shein-phone-price', 'domains' => ['shein.com'],
            'currency' => 'USD', 'adapter' => 'shein', 'is_active' => true,
        ]);
        ExchangeRate::create(['currency' => 'USD', 'rate_to_lyd' => 7, 'is_active' => true]);

        $token = str_repeat('p', 48);
        $session = [
            'cart_import_preview.'.$token => [
                'user_id' => $user->id,
                'source_url' => 'https://onelink.shein.com/58/6663yrqirv65',
                'store_id' => $store->id,
                'source_currency' => 'USD',
                'exchange_rate' => 7,
                'import_status' => 'needs_review',
                'import_message' => 'Estimated merchant pricing',
                'created_at' => now()->timestamp,
                'items' => [
                    ['_key' => 'bag-burgundy', 'name' => 'Burgundy ZERU bag', 'quantity' => 1, 'unit_price_original' => 19.08],
                    ['_key' => 'bag-black', 'name' => 'Black ZERU bag', 'quantity' => 1, 'unit_price_original' => 18.31],
                ],
            ],
        ];

        $response = $this->actingAs($user)->withSession($session)->post('/my-carts', [
            'preview_token' => $token,
            'checkout_total_from_app' => '29.63',
            'items' => [
                ['key' => 'bag-burgundy', 'quantity' => 1, 'checkout_unit_price' => '14.73'],
                ['key' => 'bag-black', 'quantity' => 1, 'checkout_unit_price' => '14.90'],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $cart = $user->carts()->firstOrFail();
        $response->assertRedirect(route('carts.show', $cart));
        $this->assertSame('37.39', $cart->subtotal_original);
        $this->assertSame('261.73', $cart->total_lyd);
        $this->assertSame('customer_manually_entered', $cart->import_meta['phone_checkout_source']);
        $this->assertSame(29.63, $cart->import_meta['phone_checkout_total']);
        $this->assertSame(29.63, $cart->import_meta['phone_checkout_comparison_total']);
        $this->assertSame(2, $cart->import_meta['phone_checkout_edited_items']);
        $this->assertFalse($cart->import_meta['phone_checkout_verified']);

        $item = $cart->items()->orderBy('id')->firstOrFail();
        $this->assertSame('19.08', $item->unit_price_original);
        $this->assertSame(14.73, $item->meta['phone_checkout_unit_price']);
        $this->assertFalse($item->meta['phone_checkout_verified']);
        $this->actingAs($user)->get(route('carts.show', $cart))
            ->assertOk()->assertSee('29.63')->assertSee('14.73');

        $this->actingAs($user)->post(route('orders.from-cart', $cart))->assertRedirect();
        $order = $user->orders()->firstOrFail();
        // The manually entered comparison must NOT become a payable amount.
        $this->assertSame('261.73', $order->total_lyd);
        $this->assertSame('19.08', $order->items()->orderBy('id')->firstOrFail()->unit_price_original);
        $this->assertSame(14.73, $order->items()->orderBy('id')->firstOrFail()->cartItem->meta['phone_checkout_unit_price']);
    }

    public function test_shein_phone_checkout_prices_reject_invalid_decimals_or_untrusted_amounts(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('q', 48);
        $session = [
            'cart_import_preview.'.$token => [
                'user_id' => $user->id,
                'source_url' => 'https://www.shein.com/ar/cart/share/landing?group_id=123456789',
                'store_id' => null,
                'source_currency' => 'USD',
                'exchange_rate' => 7,
                'import_status' => 'needs_review',
                'created_at' => now()->timestamp,
                'items' => [['_key' => 'locked', 'name' => 'Locked item', 'unit_price_original' => 20.00, 'quantity' => 1]],
            ],
        ];

        $this->actingAs($user)->withSession($session)->post('/my-carts', [
            'preview_token' => $token,
            'items' => [['key' => 'locked', 'quantity' => 1, 'checkout_unit_price' => '-1']],
        ])->assertSessionHasErrors('items.0.checkout_unit_price');

        $this->actingAs($user)->withSession($session)->post('/my-carts', [
            'preview_token' => $token,
            'checkout_total_from_app' => '12.345',
            'items' => [['key' => 'locked', 'quantity' => 1]],
        ])->assertSessionHasErrors('checkout_total_from_app');

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_phone_checkout_price_comparison_is_not_accepted_for_other_stores(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('r', 48);
        $session = [
            'cart_import_preview.'.$token => [
                'user_id' => $user->id,
                'source_url' => 'https://example.com/products',
                'store_id' => null,
                'source_currency' => 'USD',
                'exchange_rate' => 7,
                'import_status' => 'success',
                'created_at' => now()->timestamp,
                'items' => [['_key' => 'any', 'name' => 'Generic product', 'unit_price_original' => 15, 'quantity' => 1]],
            ],
        ];

        $this->actingAs($user)->withSession($session)->post('/my-carts', [
            'preview_token' => $token,
            'items' => [['key' => 'any', 'quantity' => 1, 'checkout_unit_price' => '10.00']],
        ])->assertSessionHasErrors('checkout_total_from_app');
        $this->assertDatabaseCount('carts', 0);
    }

}
