<?php
$catalog = require __DIR__.'/../../config/libya_payment_methods.php';
$methods = $catalog['methods'] ?? [];
$schemas = $catalog['schemas'] ?? [];
$codes = array_column($methods, 'code');
$required = [
    'lypay','onepay','bank_transfer','numo_qr','local_cards','visa','mastercard','pos_softpos','cash','cash_on_delivery',
    'almadar_wallet','alittihad_international_wallet','miza_wallet','daleel_libya_wallet','fawry_wallet',
    'albidaya_wallet','runpay_wallet','tadawul_cards','tafani_cards','obour_cards','masarat_mobile_cards',
    'ithmar_cards','moamalat_cards',
];
$failures = [];
$check = function (bool $ok, string $label) use (&$failures) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    if (!$ok) $failures[] = $label;
};
$check(count($methods) === 23, 'catalog has exactly 23 logical entries');
$check(count(array_unique($codes)) === count($codes), 'catalog codes are unique');
foreach ($required as $code) $check(in_array($code, $codes, true), "catalog contains {$code}");
$check(isset($schemas['lypay']['fields']['iban']), 'LYPay schema has IBAN');
$check(isset($schemas['lypay']['fields']['qr_value']), 'LYPay schema has merchant QR payload');
$check(isset($schemas['onepay']['fields']['beneficiary_account']), 'OnePay schema has beneficiary account');
$check(isset($schemas['onepay']['fields']['qr_value']), 'OnePay schema has merchant QR');
$check(isset($schemas['card_external']['fields']['acquirer_name']), 'card schema requires/accommodates acquirer');
$check(isset($schemas['wallet']['fields']['wallet_number']), 'wallet schema has wallet number');
foreach ($methods as $method) {
    $code = $method['code'] ?? 'missing';
    $check(isset($method['schema']) && isset($schemas[$method['schema']]), "{$code} references a real schema");
    $cfg = $method['config'] ?? [];
    $check(isset($cfg['integration_mode']), "{$code} defines integration mode");
    $check(isset($cfg['availability']), "{$code} defines availability");
    $check(isset($cfg['documentation_status']), "{$code} defines documentation status");
    $check(isset($cfg['official_source']), "{$code} records official source");
}
$byCode = [];
foreach ($methods as $m) $byCode[$m['code']] = $m;
$check(($byCode['lypay']['config']['integration_mode'] ?? null) === 'merchant_qr', 'LYPay defaults to merchant QR/manual verification rather than fake direct API');
$check(($byCode['onepay']['config']['documentation_status'] ?? null) === 'merchant_docs_required', 'OnePay does not invent a public merchant API');
$check(($byCode['cash']['config']['availability'] ?? null) === 'all', 'cash can be used for deposit and balance after admin enablement');
$check(($byCode['cash']['config']['allow_deposit'] ?? null) === '1', 'cash allows deposit by default in catalog configuration');
$check(($byCode['cash_on_delivery']['config']['availability'] ?? null) === 'delivery_only', 'cash on delivery is delivery-only');
$check(($byCode['pos_softpos']['config']['availability'] ?? null) === 'delivery_only', 'POS/SoftPOS is delivery-only');
exit($failures ? 1 : 0);
