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

        $response = $this->actingAs($user)->post('/my-carts', [
            'source_url'=>'https://onelink.shein.com/50/example',
            'store_id'=>$store->id,'source_currency'=>'USD','import_status'=>'success','import_message'=>'ok',
            'items'=>[
                ['name'=>'Product A','quantity'=>2,'unit_price_original'=>10],
                ['name'=>'Product B','quantity'=>1,'unit_price_original'=>5],
            ],
        ]);

        $cart = $user->carts()->firstOrFail();
        $response->assertRedirect(route('carts.show',$cart));
        $this->assertSame('25.00', $cart->subtotal_original);
        $this->assertSame('175.00', $cart->total_lyd);
        $this->assertCount(2, $cart->items);
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
}
