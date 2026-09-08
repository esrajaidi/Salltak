<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\DepositRule;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_saved_cart_as_order(): void
    {
        $user = User::factory()->create(['role'=>'customer']);
        $store = Store::create(['name'=>'SHEIN','slug'=>'shein','domains'=>['shein.com'],'currency'=>'USD','adapter'=>'shein','is_active'=>true]);
        $cart = Cart::create(['user_id'=>$user->id,'store_id'=>$store->id,'source_url'=>'https://m.shein.com/ar/cart/share/landing?group_id=1','source_host'=>'m.shein.com','source_currency'=>'USD','exchange_rate'=>7,'subtotal_original'=>10,'total_lyd'=>70,'status'=>'saved','import_status'=>'success']);
        $cart->items()->create(['name'=>'Item','quantity'=>2,'unit_price_original'=>5,'line_total_original'=>10,'currency'=>'USD','color'=>'أسود','size'=>'XL']);

        $this->actingAs($user)->post(route('orders.from-cart',$cart))->assertRedirect();
        $this->assertDatabaseHas('orders',['user_id'=>$user->id,'cart_id'=>$cart->id,'status'=>'submitted','total_lyd'=>70]);
        $this->assertDatabaseHas('order_items',['name'=>'Item','color'=>'أسود','size'=>'XL','quantity'=>2]);
    }

    public function test_admin_can_configure_deposit_and_payment_method(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post(route('admin.deposit-rules.store'),[
            'name'=>'Large','min_total'=>500,'max_total'=>null,'type'=>'percentage','value'=>50,'sort_order'=>1,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('deposit_rules',['name'=>'Large','value'=>50]);

        $this->actingAs($admin)->post(route('admin.payment-methods.store'),[
            'code'=>'test_bank','name'=>'Test Bank','type'=>'bank','sort_order'=>1,'fee_type'=>'none','fee_value'=>0,
            'instructions'=>'أرفق الإيصال','config'=>['bank_name'=>'Bank'],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payment_methods',['code'=>'test_bank','name'=>'Test Bank']);
    }

    public function test_customer_only_sees_active_payment_methods_that_support_current_due_amount(): void
    {
        $user = User::factory()->create(['role'=>'customer']);
        $order = Order::create([
            'user_id'=>$user->id,
            'status'=>'awaiting_payment',
            'payment_status'=>'unpaid',
            'subtotal_lyd'=>100,
            'total_lyd'=>100,
            'deposit_amount'=>0,
            'paid_amount'=>0,
            'remaining_amount'=>100,
        ]);

        PaymentMethod::create([
            'code'=>'active_ok','name'=>'Active OK','type'=>'bank','is_active'=>true,'sort_order'=>1,
            'min_amount'=>10,'max_amount'=>150,'fee_type'=>'none','fee_value'=>0,
            'config'=>['integration_mode'=>'manual_verification','proof_mode'=>'none'],
        ]);
        PaymentMethod::create([
            'code'=>'inactive','name'=>'Inactive Method','type'=>'bank','is_active'=>false,'sort_order'=>2,
            'fee_type'=>'none','fee_value'=>0,
            'config'=>['integration_mode'=>'manual_verification','proof_mode'=>'none'],
        ]);
        PaymentMethod::create([
            'code'=>'too_small','name'=>'Too Small Limit','type'=>'bank','is_active'=>true,'sort_order'=>3,
            'max_amount'=>50,'fee_type'=>'none','fee_value'=>0,
            'config'=>['integration_mode'=>'manual_verification','proof_mode'=>'none'],
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));
        $response->assertOk();
        $response->assertSee('Active OK');
        $response->assertDontSee('Inactive Method');
        $response->assertDontSee('Too Small Limit');
    }

    public function test_manual_payment_method_requires_configured_proof_and_disabled_method_is_rejected(): void
    {
        $user = User::factory()->create(['role'=>'customer']);
        $order = Order::create([
            'user_id'=>$user->id,
            'status'=>'awaiting_payment',
            'payment_status'=>'unpaid',
            'subtotal_lyd'=>100,
            'total_lyd'=>100,
            'deposit_amount'=>0,
            'paid_amount'=>0,
            'remaining_amount'=>100,
        ]);
        $method = PaymentMethod::create([
            'code'=>'proof_method','name'=>'Proof Method','type'=>'wallet','is_active'=>true,'sort_order'=>1,
            'fee_type'=>'none','fee_value'=>0,
            'config'=>['integration_mode'=>'manual_verification','proof_mode'=>'reference_or_receipt'],
        ]);

        $this->actingAs($user)->post(route('orders.payments.store', $order), [
            'payment_method_id'=>$method->id,
            'amount'=>100,
        ])->assertSessionHasErrors('receipt');

        $method->update(['is_active'=>false]);
        $this->actingAs($user)->post(route('orders.payments.store', $order), [
            'payment_method_id'=>$method->id,
            'amount'=>100,
            'transaction_ref'=>'TX-1',
        ])->assertSessionHasErrors('payment_method_id');
    }

    public function test_submitting_cart_marks_it_submitted_and_second_submit_reuses_same_order(): void
    {
        $user = User::factory()->create(['role'=>'customer']);
        $store = Store::create(['name'=>'SHEIN','slug'=>'shein-lock','domains'=>['shein.com'],'currency'=>'USD','adapter'=>'shein','is_active'=>true]);
        $cart = Cart::create(['user_id'=>$user->id,'store_id'=>$store->id,'source_url'=>'https://m.shein.com/ar/cart/share/landing?group_id=lock','source_host'=>'m.shein.com','source_currency'=>'USD','exchange_rate'=>7,'subtotal_original'=>10,'total_lyd'=>70,'status'=>'saved','import_status'=>'success']);
        $cart->items()->create(['name'=>'Item','quantity'=>1,'unit_price_original'=>10,'line_total_original'=>10,'currency'=>'USD']);

        $first = $this->actingAs($user)->post(route('orders.from-cart',$cart));
        $order = Order::where('cart_id',$cart->id)->firstOrFail();
        $first->assertRedirect(route('orders.show',$order));
        $this->assertSame('submitted', $cart->fresh()->status);

        $this->actingAs($user)->post(route('orders.from-cart',$cart))
            ->assertRedirect(route('orders.show',$order));

        $this->assertSame(1, Order::where('cart_id',$cart->id)->count());
    }

    public function test_submitted_cart_cannot_be_cancelled_or_deleted(): void
    {
        $user = User::factory()->create(['role'=>'customer']);
        $cart = Cart::create(['user_id'=>$user->id,'source_url'=>'https://example.com/cart','source_host'=>'example.com','source_currency'=>'USD','exchange_rate'=>7,'subtotal_original'=>10,'total_lyd'=>70,'status'=>'submitted','import_status'=>'success']);
        Order::create(['user_id'=>$user->id,'cart_id'=>$cart->id,'status'=>'submitted','payment_status'=>'unpaid','subtotal_lyd'=>70,'total_lyd'=>70,'remaining_amount'=>70,'submitted_at'=>now()]);

        $this->actingAs($user)->patch(route('carts.cancel',$cart))
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseHas('carts',['id'=>$cart->id,'status'=>'submitted']);

        $this->actingAs($user)->delete(route('carts.destroy',$cart))
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseHas('carts',['id'=>$cart->id]);
    }

    public function test_saved_payment_terms_remain_selected_and_populated_after_reload(): void
    {
        $admin = User::factory()->create(['role'=>'admin', 'is_active'=>true]);
        $customer = User::factory()->create(['role'=>'customer']);
        $order = Order::create([
            'user_id'=>$customer->id,
            'status'=>'under_review',
            'payment_status'=>'unpaid',
            'subtotal_lyd'=>100,
            'total_lyd'=>100,
            'deposit_amount'=>0,
            'paid_amount'=>0,
            'remaining_amount'=>100,
            'review_completed_at'=>now(),
        ]);
        $order->items()->create([
            'name'=>'Reviewed item', 'quantity'=>1, 'unit_price_original'=>10,
            'unit_price_lyd'=>100, 'line_total_lyd'=>100, 'currency'=>'USD', 'review_status'=>'approved',
        ]);

        $this->actingAs($admin)->post(route('admin.orders.approve', $order), [
            'deposit_mode'=>'none',
            'payment_terms_note'=>'الدفع الكامل قبل الشراء.',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('none', $order->deposit_type);
        $this->assertSame('0.00', $order->deposit_value);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('value="none" selected', false)
            ->assertSee('الدفع الكامل قبل الشراء.');
    }

    public function test_verifying_full_payment_moves_pre_purchase_order_to_ready_for_purchase(): void
    {
        $admin = User::factory()->create(['role'=>'admin', 'is_active'=>true]);
        $customer = User::factory()->create(['role'=>'customer']);
        $order = Order::create([
            'user_id'=>$customer->id,
            'status'=>'awaiting_payment',
            'payment_status'=>'unpaid',
            'subtotal_lyd'=>100,
            'total_lyd'=>100,
            'deposit_required'=>false,
            'deposit_type'=>'none',
            'deposit_value'=>0,
            'deposit_amount'=>0,
            'paid_amount'=>0,
            'remaining_amount'=>100,
        ]);
        $method = PaymentMethod::create([
            'code'=>'full_payment_test','name'=>'Full Payment Test','type'=>'cash','is_active'=>true,'sort_order'=>1,
            'fee_type'=>'none','fee_value'=>0,'config'=>['integration_mode'=>'manual_verification','proof_mode'=>'none'],
        ]);
        $payment = Payment::create([
            'order_id'=>$order->id,
            'payment_method_id'=>$method->id,
            'user_id'=>$customer->id,
            'amount'=>100,
            'fee_amount'=>0,
            'status'=>'pending_verification',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $payment]), [
            'decision'=>'verified',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('0.00', $order->remaining_amount);
        $this->assertSame('ready_for_purchase', $order->status);
    }

}
