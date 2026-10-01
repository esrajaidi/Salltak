<?php

declare(strict_types=1);

use App\Services\CartImport\SheinPreviewGuard;

require_once dirname(__DIR__, 2).'/app/Services/CartImport/SheinPreviewGuard.php';

function check(bool $condition, string $description): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
}

$complete = [
    'source_url' => 'https://m.shein.com/ar/cart/share/landing?shc=EXAMPLE_ONLY',
    'source_currency' => 'USD',
    'import_status' => 'success',
    'share_complete' => true,
    'expected_count' => 2,
    'items' => [
        ['_key' => 'first', 'quantity' => 1, 'unit_price_original' => 10.62, 'price_status' => 'confirmed', 'price_source' => 'network_exact_sku'],
        ['_key' => 'second', 'quantity' => 2, 'unit_price_original' => 9.59, 'price_status' => 'confirmed', 'price_source' => 'visible_same_card'],
    ],
];

check(SheinPreviewGuard::isSheinShare($complete['source_url']), 'SHEIN share recognized');
check(SheinPreviewGuard::isSheinShare('https://onelink.shein.com/54/example'), 'OneLink recognized');
check(!SheinPreviewGuard::isSheinShare('https://not-shein.com/cart/share'), 'host suffix is exact');
check(SheinPreviewGuard::canSave($complete), 'fully verified SHEIN share can be saved');
check(!SheinPreviewGuard::requiresReview($complete), 'verified share needs no review');

$cases = [
    'not complete' => ['share_complete' => false],
    'unknown completeness' => ['share_complete' => null],
    'wrong status' => ['import_status' => 'needs_review'],
    'wrong currency' => ['source_currency' => 'AED'],
    'missing expected count' => ['expected_count' => null],
    'count differs' => ['expected_count' => 3],
    'zero count' => ['expected_count' => 0],
    'empty list' => ['items' => []],
];
foreach ($cases as $description => $fields) {
    $unverified = array_replace($complete, $fields);
    check(!SheinPreviewGuard::canSave($unverified), $description);
    check(SheinPreviewGuard::requiresReview($unverified), $description.' stays review-only');
}

foreach ([
    'missing price' => ['unit_price_original' => null],
    'zero price' => ['unit_price_original' => 0],
    'missing status' => ['price_status' => null],
    'wrong status' => ['price_status' => 'unconfirmed'],
    'missing source' => ['price_source' => null],
    'unknown source' => ['price_source' => 'fuzzy_name'],
    'invalid quantity' => ['quantity' => 0],
] as $description => $fields) {
    $unverified = $complete;
    $unverified['items'][1] = array_replace($unverified['items'][1], $fields);
    check(!SheinPreviewGuard::canSave($unverified), $description.' must block the entire cart');
}

$generic = $complete;
$generic['source_url'] = 'https://example.com/share';
$generic['share_complete'] = false;
check(SheinPreviewGuard::canSave($generic), 'unrelated stores retain their prior save flow');

echo "PASS: SHEIN preview guard rejects incomplete and unverified carts.\n";
