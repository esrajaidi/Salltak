<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_finishing_review_auto_approves_items_without_issues(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);
        $item = $this->makeItem($order);

        $this->actingAs($admin)
            ->post(route('admin.orders.review.complete', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $item->fresh()->review_status);
        $this->assertSame('under_review', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->review_completed_at);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('تم إنهاء المراجعة')
            ->assertDontSee('data-confirm-title="إنهاء مراجعة الطلب"', false);
    }

    public function test_staff_marks_only_problem_items_and_customer_is_asked_after_finishing_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);
        $problemItem = $this->makeItem($order, 'منتج بمقاس غير متوفر');
        $healthyItem = $this->makeItem($order, 'منتج سليم');

        $this->actingAs($admin)
            ->patch(route('admin.orders.items.review', [$order, $problemItem]), [
                'issue_type' => 'size_unavailable',
                'review_reason' => 'المقاس XXL غير متوفر حاليًا.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('option_issue', $problemItem->fresh()->review_status);
        $this->assertStringContainsString('المقاس غير متوفر', (string) $problemItem->fresh()->review_reason);
        $this->assertSame('submitted', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.orders.review.complete', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $healthyItem->fresh()->review_status);
        $this->assertSame('needs_customer_action', $order->fresh()->status);
    }

    public function test_price_change_requires_the_new_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);
        $item = $this->makeItem($order);

        $this->actingAs($admin)
            ->patch(route('admin.orders.items.review', [$order, $item]), [
                'issue_type' => 'price_changed',
            ])
            ->assertSessionHasErrors('reviewed_unit_price_lyd');
    }

    private function makeOrder(User $customer): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'status' => 'submitted',
            'payment_status' => 'unpaid',
            'subtotal_lyd' => 70,
            'total_lyd' => 70,
            'deposit_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 70,
        ]);
    }

    private function makeItem(Order $order, string $name = 'منتج تجريبي'): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'name' => $name,
            'quantity' => 1,
            'unit_price_original' => 10,
            'unit_price_lyd' => 70,
            'line_total_lyd' => 70,
            'currency' => 'USD',
            'review_status' => 'pending',
        ]);
    }
}
