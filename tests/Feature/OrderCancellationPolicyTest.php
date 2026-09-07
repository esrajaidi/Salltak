<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_deposit_requires_reason_and_policy_acknowledgement_before_customer_cancellation(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, deposit: 30, paid: 30, status: 'deposit_paid');

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order), ['reason' => 'لم أعد أرغب في متابعة الطلب.'])
            ->assertSessionHasErrors('cancellation_policy_acknowledged');

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order), ['cancellation_policy_acknowledged' => '1'])
            ->assertSessionHasErrors('reason');
    }

    public function test_acknowledged_paid_deposit_cancellation_keeps_payment_and_records_forfeiture_in_history(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, deposit: 30, paid: 30, status: 'deposit_paid');

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order), [
                'reason' => 'أرغب في إلغاء الطلب قبل بدء الشراء.',
                'cancellation_policy_acknowledged' => '1',
            ])
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('30.00', $order->paid_amount);
        $this->assertSame('0.00', $order->remaining_amount);

        $history = $order->histories()->latest('id')->firstOrFail();
        $this->assertSame('customer', $history->visibility);
        $this->assertSame('ألغى العميل الطلب. السبب: أرغب في إلغاء الطلب قبل بدء الشراء.', $history->note);
        $this->assertSame('أرغب في إلغاء الطلب قبل بدء الشراء.', $history->metadata['cancellation_reason']);
        $this->assertTrue($history->metadata['deposit_forfeited']);
        $this->assertSame(30.0, (float) $history->metadata['forfeited_deposit_amount']);
    }

    public function test_unpaid_order_can_be_cancelled_with_reason_without_deposit_acknowledgement(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, deposit: 0, paid: 0, status: 'under_review');

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order), ['reason' => 'أرغب في إلغاء الطلب.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_customer_cannot_self_cancel_when_verified_payments_exceed_the_deposit(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, deposit: 30, paid: 50, status: 'awaiting_payment');

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order), [
                'reason' => 'أرغب في الإلغاء.',
                'cancellation_policy_acknowledged' => '1',
            ])
            ->assertSessionHasErrors('order');

        $this->assertSame('awaiting_payment', $order->fresh()->status);
    }

    private function makeOrder(User $customer, float $deposit, float $paid, string $status): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'status' => $status,
            'payment_status' => $paid > 0 ? 'deposit_paid' : 'unpaid',
            'subtotal_lyd' => 100,
            'total_lyd' => 100,
            'deposit_required' => $deposit > 0,
            'deposit_type' => $deposit > 0 ? 'fixed' : 'none',
            'deposit_value' => $deposit,
            'deposit_amount' => $deposit,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, 100 - $paid),
        ]);
    }
}
