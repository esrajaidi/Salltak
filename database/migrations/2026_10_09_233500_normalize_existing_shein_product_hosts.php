<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only product links, never the original cart share URLs, account
        // sessions, items, quantities, prices, or order status.
        foreach (['cart_items', 'order_items'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->where('product_url', 'like', 'https://ar.shein.com/%')
                ->update([
                    'product_url' => DB::raw("REPLACE(product_url, 'https://ar.shein.com/', 'https://www.shein.com/')"),
                ]);

            // Older imported product pages may have been saved on the mobile
            // host. Keep share links/endpoints unchanged.
            DB::table($table)
                ->where('product_url', 'like', 'https://m.shein.com/%')
                ->where('product_url', 'not like', '%/cart/share/%')
                ->where('product_url', 'not like', '%/bff-api/%')
                ->update([
                    'product_url' => DB::raw("REPLACE(product_url, 'https://m.shein.com/', 'https://www.shein.com/')"),
                ]);
        }
    }

    public function down(): void
    {
        // No rollback of normalized product URLs: the original host of each
        // row cannot safely be reconstructed. Data remains intact.
    }
};
