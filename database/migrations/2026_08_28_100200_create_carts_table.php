<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_url');
            $table->string('source_host')->nullable();
            $table->string('source_currency', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('subtotal_original', 14, 2)->default(0);
            $table->decimal('total_lyd', 14, 2)->default(0);
            $table->string('status')->default('saved')->index();
            $table->string('import_status')->default('needs_review')->index();
            $table->text('import_message')->nullable();
            $table->json('import_meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('carts'); }
};
