<?php

require __DIR__.'/../../app/Services/CartImport/SheinProductUrl.php';

use App\Services\CartImport\SheinProductUrl;

$examples = [
    'https://ar.shein.com/product-p-61586172.html' => 'https://www.shein.com/product-p-61586172.html',
    'https://m.shein.com/ar/test-p-12345.html?sku=ABC#details' => 'https://www.shein.com/ar/test-p-12345.html?sku=ABC#details',
    'http://shein.com/product-p-12345.html' => 'https://www.shein.com/product-p-12345.html',
    'https://www.shein.com/product-p-12345.html' => 'https://www.shein.com/product-p-12345.html',
    'https://onelink.shein.com/56/abcde12345?shc=ABC' => 'https://onelink.shein.com/56/abcde12345?shc=ABC',
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795' => 'https://m.shein.com/ar/cart/share/landing?group_id=851956795',
    'https://m.shein.com/ar/bff-api/order/cart/share/landing' => 'https://m.shein.com/ar/bff-api/order/cart/share/landing',
    'https://ar.shein.com.evil.example/product-p-11.html' => 'https://ar.shein.com.evil.example/product-p-11.html',
    'https://images.ltwebstatic.com/abc.jpg' => 'https://images.ltwebstatic.com/abc.jpg',
];
foreach ($examples as $source => $expected) {
    $result = SheinProductUrl::canonicalize($source);
    if ($result !== $expected) {
        throw new RuntimeException('Unexpected normalization: '.$source.' → '.$result);
    }
}

$parser = file_get_contents(__DIR__.'/../../scripts/shein-share-bff-items.mjs');
$adapter = file_get_contents(__DIR__.'/../../app/Services/CartImport/Adapters/SheinShareAdapter.php');
$login = file_get_contents(__DIR__.'/../../scripts/shein-login-local.mjs');
if (! str_contains($parser, "'https://www.shein.com/product-p-'")
    || str_contains($parser, "'https://ar.shein.com/product-p-'")
    || substr_count($adapter, 'SheinProductUrl::canonicalize(') < 3
    || ! str_contains($login, "page.goto('https://www.shein.com/'")) {
    throw new RuntimeException('A SHEIN product URL or login still points to a non-www storefront.');
}

echo "SHEIN canonical website and product link checks passed.\n";
