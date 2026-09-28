<?php

declare(strict_types=1);

use App\Services\CartImport\Browser\SheinImportDiagnostics;

$helper = dirname(__DIR__, 2).'/app/Services/CartImport/Browser/SheinImportDiagnostics.php';
if (! is_file($helper)) {
    fwrite(STDERR, "FAIL: safe SHEIN diagnostics helper has not been implemented.\n");
    exit(1);
}
require_once $helper;

function check(bool $condition, string $description): void
{
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
}

$traceId = 'b454b80a-ec11-4a62-93d6-df2735849012';
$attempt = SheinImportDiagnostics::attempt([
    'status' => 'missing_usd_prices',
    'final_url' => 'https://m.shein.com/ar/cart/share/landing?shc=SECRET_CART_TOKEN&group_id=SECRET_GROUP',
    'message' => 'private@example.com SECRET_CART_TOKEN',
    'stderr' => 'secret cookie and private@example.com',
    'items' => [],
    'meta' => [
        'visible_product_count' => 7,
        'candidate_root_count' => 12,
        'image_candidate_count' => 8,
        'missing_usd_price_count' => 3,
        'network_usd_price_count' => 4,
        'inspected_response_count' => 2,
    ],
    'exit_code' => 0,
], 'shared_desktop', $traceId, 5021);

check($attempt['trace_id'] === $traceId, 'trace ID is preserved');
check($attempt['stage'] === 'shared_desktop', 'attempt stage is present');
check($attempt['status'] === 'missing_usd_prices', 'status is present');
check($attempt['failure_reason'] === 'usd_price_unconfirmed', 'safe failure category is present');
check($attempt['final_host'] === 'm.shein.com', 'SHEIN host is recognized');
check($attempt['final_page'] === 'share_landing', 'only a coarse page category is recorded');
check($attempt['visible_product_count'] === 7, 'visible product count is present');
check($attempt['missing_usd_price_count'] === 3, 'USD diagnostic count is present');
check($attempt['duration_ms'] === 5021, 'duration is present');
check($attempt['exit_code'] === 0, 'worker exit status is present');
$json = json_encode($attempt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
foreach (['SECRET_CART_TOKEN', 'SECRET_GROUP', 'private@example.com', 'group_id', 'cookie', 'landing?', '/ar/cart/share/landing'] as $secret) {
    check(! str_contains((string) $json, $secret), "secret {$secret} must never appear in logs");
}

$untrusted = SheinImportDiagnostics::attempt([
    'status' => 'session_token=SECRET_CART_TOKEN',
    'final_url' => 'https://private.example.com/cart?secret=SECRET_CART_TOKEN',
    'meta' => ['visible_product_count' => -30, 'candidate_root_count' => 'secret'],
], 'stage=SECRET_CART_TOKEN', $traceId, -20);
check($untrusted['status'] === 'other', 'unrecognized status is redacted');
check($untrusted['stage'] === 'other', 'unrecognized stage is redacted');
check($untrusted['final_host'] === 'external', 'unrecognized host is redacted');
check($untrusted['final_page'] === 'other', 'unrecognized page path is redacted');
check($untrusted['visible_product_count'] === 0, 'negative counts are clamped');
check($untrusted['candidate_root_count'] === 0, 'non-numeric counts are ignored');
check($untrusted['duration_ms'] === 0, 'negative duration is clamped');
check(! str_contains(json_encode($untrusted), 'SECRET_CART_TOKEN'), 'untrusted fields do not leak');

echo "PASS: safe SHEIN attempt diagnostics; no raw share tokens, URLs, error bodies or product data.\n";
