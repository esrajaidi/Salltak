<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deposit_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_total', 14, 2)->default(0);
            $table->decimal('max_total', 14, 2)->nullable();
            $table->string('type')->default('percentage');
            $table->decimal('value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('deposit_rules'); }
};
