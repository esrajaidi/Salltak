<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveBootstrapUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_layout_loads_bootstrap_and_rtl_markup(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('vendor/bootstrap/bootstrap.min.css', false)
            ->assertSee('bootstrap.rtl.min.css', false)
            ->assertSee('navbar-expand-lg', false)
            ->assertSee('col-lg-7', false);
    }

    public function test_customer_cart_index_uses_responsive_bootstrap_grid(): void
    {
        $user = User::factory()->create();
        $store = Store::create([
            'name' => 'SHEIN',
            'slug' => 'shein-responsive-test',
            'domains' => ['shein.com'],
            'currency' => 'USD',
            'adapter' => 'shein',
            'is_active' => true,
        ]);
        Cart::create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'source_url' => 'https://m.shein.com/ar/cart/share/landing?group_id=responsive-test',
            'source_host' => 'm.shein.com',
            'source_currency' => 'USD',
            'exchange_rate' => 7,
            'subtotal_original' => 10,
            'total_lyd' => 70,
            'status' => 'saved',
            'import_status' => 'success',
        ]);

        $this->actingAs($user)->get('/my-carts')
            ->assertOk()
            ->assertSee('row g-3 g-lg-4', false)
            ->assertSee('col-md-6 col-xl-4', false);
    }

    public function test_admin_dashboard_uses_responsive_offcanvas_sidebar(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('offcanvas-lg offcanvas-start admin-sidebar', false)
            ->assertSee('admin-content p-3 p-md-4 p-xl-5', false);
    }
}
