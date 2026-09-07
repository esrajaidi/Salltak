<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('product_url')->nullable();
            $table->text('image_url')->nullable();
            $table->string('variant')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price_original', 14, 2)->default(0);
            $table->decimal('unit_price_lyd', 14, 2)->default(0);
            $table->decimal('reviewed_unit_price_lyd', 14, 2)->nullable();
            $table->decimal('line_total_lyd', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('review_status')->default('pending')->index();
            $table->text('review_reason')->nullable();
            $table->string('customer_decision')->nullable();
            $table->text('customer_reply')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('order_items'); }
};
