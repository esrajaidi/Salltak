<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('review_completed_at')->nullable()->after('reviewed_at');
        });

        // Preserve review completion for orders completed before this column existed.
        if (Schema::hasTable('audit_logs')) {
            $completedReviews = DB::table('audit_logs')
                ->select('order_id', DB::raw('MIN(created_at) as completed_at'))
                ->where('event_type', 'order.review_completed')
                ->whereNotNull('order_id')
                ->groupBy('order_id')
                ->get();

            foreach ($completedReviews as $review) {
                DB::table('orders')
                    ->where('id', $review->order_id)
                    ->whereNull('review_completed_at')
                    ->update(['review_completed_at' => $review->completed_at]);
            }
        }


        // Reconcile orders that were already fully paid before automatic status synchronization existed.
        $fullyPaidOrders = DB::table('orders')
            ->select('id', 'status')
            ->whereIn('status', ['approved', 'awaiting_deposit', 'awaiting_payment', 'deposit_paid'])
            ->where('payment_status', 'paid')
            ->where('total_lyd', '>', 0)
            ->where('remaining_amount', '<=', 0.009)
            ->get();

        foreach ($fullyPaidOrders as $paidOrder) {
            DB::table('orders')->where('id', $paidOrder->id)->update([
                'status' => 'ready_for_purchase',
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('order_status_histories')) {
                DB::table('order_status_histories')->insert([
                    'order_id' => $paidOrder->id,
                    'user_id' => null,
                    'from_status' => $paidOrder->status,
                    'to_status' => 'ready_for_purchase',
                    'event_type' => 'status_changed',
                    'visibility' => 'customer',
                    'note' => 'تمت مزامنة الطلب بعد اكتمال الدفع وأصبح جاهزًا للشراء.',
                    'metadata' => json_encode(['event' => 'payment_status_reconciled'], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('review_completed_at');
        });
    }
};
