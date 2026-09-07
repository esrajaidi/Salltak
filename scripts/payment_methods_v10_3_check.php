<?php
$catalog = require __DIR__.'/../config/libya_payment_methods.php';
$methods = $catalog['methods'] ?? [];
$schemas = $catalog['schemas'] ?? [];
$failures = 0;
$codes = array_column($methods, 'code');
$checks = [
    'catalog has 22 entries' => count($methods) === 22,
    'codes unique' => count(array_unique($codes)) === count($codes),
    'LYPay exists' => in_array('lypay', $codes, true),
    'OnePay exists' => in_array('onepay', $codes, true),
    'Visa exists' => in_array('visa', $codes, true),
    'Mastercard exists' => in_array('mastercard', $codes, true),
    'NUMO QR exists' => in_array('numo_qr', $codes, true),
    'POS/SoftPOS exists' => in_array('pos_softpos', $codes, true),
    'all CBL wallet/card providers remain represented' => count(array_intersect([
        'almadar_wallet','alittihad_international_wallet','miza_wallet','daleel_libya_wallet','fawry_wallet','albidaya_wallet','runpay_wallet',
        'tadawul_cards','tafani_cards','obour_cards','masarat_mobile_cards','ithmar_cards','moamalat_cards'
    ], $codes)) === 13,
    'LYPay schema uses IBAN' => isset($schemas['lypay']['fields']['iban']),
    'OnePay schema uses beneficiary account' => isset($schemas['onepay']['fields']['beneficiary_account']),
    'card schema requires acquirer' => isset($schemas['card_external']['fields']['acquirer_name']),
];
foreach ($checks as $name=>$ok) { echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL; if(!$ok)$failures++; }
$files = [
    'model'=>file_get_contents(__DIR__.'/../app/Models/PaymentMethod.php'),
    'admin'=>file_get_contents(__DIR__.'/../resources/views/admin/payment-methods/index.blade.php'),
    'order'=>file_get_contents(__DIR__.'/../resources/views/orders/show.blade.php'),
];
$static = [
    'activation readiness guard' => str_contains($files['model'],'activationIssues'),
    'order-aware customer filtering' => str_contains($files['model'],'canOfferForOrder'),
    'compact admin grid' => str_contains($files['admin'],'payment-method-grid'),
    'modal configuration UI' => str_contains($files['admin'],'modal fade'),
    'external payment link UI' => str_contains($files['order'],'externalPaymentUrl'),
];
foreach ($static as $name=>$ok) { echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL; if(!$ok)$failures++; }
echo PHP_EOL.(count($checks)+count($static)).' checks, '.$failures.' failures'.PHP_EOL;
exit($failures?1:0);
