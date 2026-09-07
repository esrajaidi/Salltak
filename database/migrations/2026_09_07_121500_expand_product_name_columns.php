<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'name')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->text('name')->change();
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'name')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->text('name')->change();
            });
        }
    }

    public function down(): void
    {
        // Keep TEXT on rollback to avoid truncating product names already stored by the importer.
    }
};
