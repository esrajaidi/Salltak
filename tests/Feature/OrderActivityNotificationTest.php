<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_note_is_audited_but_not_notified_to_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'status' => 'under_review',
            'payment_status' => 'unpaid',
            'subtotal_lyd' => 100,
            'total_lyd' => 100,
            'remaining_amount' => 100,
        ]);

        app(OrderWorkflowService::class)->addNote($order, $admin, 'ملاحظة تشغيلية داخلية', 'internal');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'event_type' => 'note',
            'visibility' => 'internal',
            'note' => 'ملاحظة تشغيلية داخلية',
        ]);
        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $customer->id,
            'body' => 'ملاحظة تشغيلية داخلية',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'order_id' => $order->id,
            'event_type' => 'order.note_added',
        ]);
    }

    public function test_customer_visible_note_creates_notification(): void
    {
        $manager = User::factory()->create(['role' => 'order_manager']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'status' => 'under_review',
            'payment_status' => 'unpaid',
            'subtotal_lyd' => 100,
            'total_lyd' => 100,
            'remaining_amount' => 100,
        ]);

        app(OrderWorkflowService::class)->addNote($order, $manager, 'نحتاج تأكيد اللون قبل المتابعة', 'customer');

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $customer->id,
            'type' => 'order.note',
            'body' => 'نحتاج تأكيد اللون قبل المتابعة',
        ]);
    }

    public function test_status_transition_records_visibility_and_notifies_customer_without_exposing_internal_note(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'unpaid',
            'subtotal_lyd' => 100,
            'total_lyd' => 100,
            'remaining_amount' => 100,
        ]);

        app(OrderWorkflowService::class)->transition($order, 'awaiting_payment', $admin, 'مراجعة داخلية للحساب', 'internal');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'approved',
            'to_status' => 'awaiting_payment',
            'visibility' => 'internal',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $customer->id,
            'type' => 'order.status_changed',
        ]);
        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $customer->id,
            'body' => 'مراجعة داخلية للحساب',
        ]);
    }

    public function test_order_manager_can_open_home_without_custom_role_method_dependency(): void
    {
        $manager = User::factory()->create(['role' => 'order_manager', 'is_active' => true]);
        $this->actingAs($manager)->get(route('home'))->assertOk();
    }
}
