<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderDecisionNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejection_requires_a_reason_and_is_visible_to_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, 'under_review');

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'rejected'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => 'rejected',
                'reason' => 'تعذر اعتماد الطلب لأن الرابط لم يعد صالحًا.',
                'visibility' => 'auto',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'rejected',
            'visibility' => 'customer',
            'note' => 'تعذر اعتماد الطلب لأن الرابط لم يعد صالحًا.',
        ]);
    }

    public function test_approval_accepts_optional_customer_note_and_preserves_it_in_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, 'under_review');
        OrderItem::create([
            'order_id' => $order->id,
            'name' => 'منتج تجريبي',
            'quantity' => 1,
            'unit_price_original' => 10,
            'unit_price_lyd' => 70,
            'line_total_lyd' => 70,
            'currency' => 'USD',
            'review_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.approve', $order), [
                'deposit_mode' => 'none',
                'decision_note' => 'تم قبول طلبك بعد مراجعة جميع المنتجات.',
                'payment_terms_note' => 'الدفع الكامل قبل الشراء.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'approved',
            'visibility' => 'customer',
            'note' => 'تم قبول طلبك بعد مراجعة جميع المنتجات.',
        ]);
    }

    public function test_every_note_is_saved_as_a_separate_history_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, 'under_review');
        $workflow = app(OrderWorkflowService::class);

        $workflow->addNote($order, $admin, 'الملاحظة الأولى', 'internal');
        $workflow->addNote($order, $admin, 'الملاحظة الثانية', 'internal');
        $workflow->addNote($order, $admin, 'ملاحظة تظهر للعميل', 'customer');

        $this->assertSame(3, $order->histories()->where('event_type', 'note')->count());
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'note' => 'الملاحظة الأولى']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'note' => 'الملاحظة الثانية']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'note' => 'ملاحظة تظهر للعميل', 'visibility' => 'customer']);
    }

    public function test_customer_visible_updates_can_send_email_to_customer(): void
    {
        Mail::fake();
        SystemSetting::updateOrCreate(['key' => 'notify_email_customer_updates'], ['value' => '1']);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'email' => 'customer@example.com']);
        $order = $this->makeOrder($customer, 'under_review');

        app(OrderWorkflowService::class)->transition(
            $order,
            'needs_customer_action',
            $admin,
            'نحتاج منك تأكيد المقاس قبل المتابعة.'
        );

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $customer->id,
            'type' => 'order.status_changed',
            'body' => 'نحتاج منك تأكيد المقاس قبل المتابعة.',
        ]);
        Mail::assertSentCount(1);
    }

    private function makeOrder(User $customer, string $status): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'unpaid',
            'subtotal_lyd' => 70,
            'total_lyd' => 70,
            'deposit_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 70,
        ]);
    }
}
