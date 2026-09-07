<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_manager_can_open_backoffice_orders_but_not_payment_configuration(): void
    {
        $manager = User::factory()->create(['role'=>'order_manager','is_active'=>true]);

        $this->actingAs($manager)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.payment-methods.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.deposit-rules.index'))->assertForbidden();
    }
}
