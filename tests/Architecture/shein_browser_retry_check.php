<?php

use App\Services\CartImport\Browser\SheinBrowserImporter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Process;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$url = 'https://m.shein.com/ar/cart/share/landing?shc=2_RwCnM9DrOvA&group_id=851956795&local_country=AE&url_from=GM71035002695&cart_share=1';

$emptyLoaded = json_encode([
    'ok' => true,
    'status' => 'loaded',
    'items' => [],
    'payloads' => [],
    'meta' => [
        'network_item_count' => 0,
        'final_item_count' => 0,
        'direct_bff_status' => 200,
        'direct_bff_matched_items' => 0,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$successfulRetry = json_encode([
    'ok' => true,
    'status' => 'loaded',
    'items' => [[
        'external_id' => '10001',
        'name' => 'منتج من السلة',
        'product_url' => 'https://m.shein.com/ar/example-p-10001.html',
        'image_url' => 'https://img.ltwebstatic.com/example.jpg',
        'variant' => 'SKU-10001-M',
        'color' => 'Black',
        'size' => 'M',
        'quantity' => 2,
        'unit_price_original' => 14.85,
        'currency' => 'USD',
    ]],
    'payloads' => [],
    'meta' => [
        'network_item_count' => 1,
        'final_item_count' => 1,
        'direct_bff_status' => 200,
        'direct_bff_matched_items' => 1,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$sequence = Process::sequence()
    ->push(Process::result(output: $emptyLoaded))
    ->push(Process::result(output: $successfulRetry));

Process::fake(['*' => $sequence]);

$result = $app->make(SheinBrowserImporter::class)->import($url);

if (count($result['items'] ?? []) !== 1) {
    fwrite(STDERR, "[FAIL] Browser importer did not retry an empty loaded SHEIN response.\n");
    fwrite(STDERR, 'Returned item count: '.count($result['items'] ?? [])."\n");
    exit(1);
}

Process::assertRanTimes([
    (string) config('services.cart_import.shein_browser.node_binary', 'node'),
    base_path('scripts/shein-browser-import.mjs'),
], 2);

if (($result['meta']['import_attempt_count'] ?? null) !== 2) {
    fwrite(STDERR, "[FAIL] Retry diagnostics did not report two attempts.\n");
    exit(1);
}

fwrite(STDOUT, "[PASS] Empty loaded SHEIN responses are retried and the successful retry is returned.\n");
