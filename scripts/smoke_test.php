<?php
require __DIR__.'/../app/Services/MoneyCalculator.php';
require __DIR__.'/../app/Services/StoreUrlClassifier.php';
require __DIR__.'/../app/Services/CartImport/Contracts/CartSourceAdapter.php';
require __DIR__.'/../app/Services/CartImport/ImportResult.php';
require __DIR__.'/../app/Services/CartImport/Browser/SheinBrowserImporter.php';
require __DIR__.'/../app/Services/CartImport/Adapters/SheinShareAdapter.php';

use App\Services\CartImport\Adapters\SheinShareAdapter;
use App\Services\CartImport\Browser\SheinBrowserImporter;
use App\Services\MoneyCalculator;
use App\Services\StoreUrlClassifier;

$money = new MoneyCalculator();
$classifier = new StoreUrlClassifier();

$adapter = new SheinShareAdapter($classifier, new SheinBrowserImporter());
$reflection = new ReflectionClass($adapter);
$extract = $reflection->getMethod('extractProducts');
$extract->setAccessible(true);
$sampleHtml = <<<'HTML'
<html><script>window.__INITIAL_STATE__ = {"cartShareData":{"goods_list":[{"goods_id":"1","goods_name":"Dress","goods_img":"//img.test/a.jpg","salePrice":{"amount":"49.90","amountWithSymbol":"SR49.90","usdAmount":"13.29","usdAmountWithSymbol":"$13.29","currency":"AED"},"cart_quantity":2,"size":"M","color":"Black"},{"goods_id":"2","goods_name":"Bag","goods_img":"//img.test/b.jpg","salePrice":{"amount":"25","amountWithSymbol":"SR25.00","usdAmount":"6.66","usdAmountWithSymbol":"$6.66","currency":"AED"},"quantity":1}]}};</script></html>
HTML;
$sampleItems = $extract->invoke(
    $adapter,
    $sampleHtml,
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE&cart_share=1',
    'AED'
);


$escapedHtml = <<<'HTML'
<html><script>self.__next_f.push([1,"{\"cartShareData\":{\"goods_list\":[{\"quantity\":3,\"goods_info\":{\"goods_id\":\"3\",\"goods_name\":\"Shoes\",\"goods_img\":{\"origin_image\":\"//img.test/c.jpg\"},\"sale_price\":{\"amount\":\"19.50\",\"currency\":\"AED\"},\"sku_sale_attr\":[{\"attr_name\":\"Color\",\"attr_value\":\"White\"},{\"attr_name\":\"Size\",\"attr_value\":\"39\"}]}}]}}"]);</script></html>
HTML;
$escapedItems = $extract->invoke(
    $adapter,
    $escapedHtml,
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE&cart_share=1',
    'AED'
);

$extractBrowser = $reflection->getMethod('extractBrowserProducts');
$extractBrowser->setAccessible(true);
$browserItems = $extractBrowser->invoke(
    $adapter,
    [
        'status' => 'loaded',
        'items' => [[
            'external_id' => '99112233',
            'name' => 'Browser Dress',
            'product_url' => 'https://m.shein.com/ar/example-p-99112233.html',
            'image_url' => 'https://img.ltwebstatic.com/browser.jpg',
            'variant' => 'SKU-M',
            'color' => 'Black',
            'size' => 'M',
            'quantity' => 2,
            'unit_price_original' => 55.75,
            'currency' => 'AED',
        ]],
        'payloads' => [],
    ],
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE&cart_share=1',
    'AED'
);


$recommendHtml = <<<'HTML'
<html><script>window.__INITIAL_STATE__ = {"cartShareData":{"goods_list":[{"goods_id":"10","goods_name":"Real Cart Dress","salePrice":{"amount":"40","currency":"AED"},"cart_quantity":1}]},"recommendation":{"goods_list":[{"goods_id":"99","goods_name":"Recommended Bag","salePrice":{"amount":"10","currency":"AED"},"sku_id":"R-99","color":"Pink"}]}};</script></html>
HTML;
$recommendItems = $extract->invoke(
    $adapter,
    $recommendHtml,
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE&cart_share=1',
    'AED'
);

$browserWithPayloadNoise = $extractBrowser->invoke(
    $adapter,
    [
        'status' => 'loaded',
        'items' => [[
            'external_id' => '11',
            'name' => 'Real Browser Cart Item',
            'product_url' => 'https://m.shein.com/ar/real-p-11.html',
            'image_url' => 'https://img.ltwebstatic.com/real.jpg',
            'variant' => '', 'color' => '', 'size' => '',
            'quantity' => 1,
            'unit_price_original' => 20,
            'currency' => 'AED',
        ]],
        'payloads' => [[
            'recommendation' => [
                'goods_list' => [[
                    'goods_id' => '999',
                    'goods_name' => 'Noise Recommendation',
                    'salePrice' => ['amount' => '5', 'currency' => 'AED'],
                    'quantity' => 1,
                ]],
            ],
        ]],
    ],
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE&cart_share=1',
    'AED'
);

$checks = [
    'line total' => $money->lineTotal(12.5, 3) === 37.5,
    'LYD conversion' => $money->toLyd(25, 7) === 175.0,
    'SHEIN onelink detection' => $classifier->isShein('https://onelink.shein.com/50/abc?shc=1') === true,
    'SHEIN share landing detection' => $classifier->isShein('https://m.shein.com/ar/cart/share/landing?group_id=851956795&cart_share=1') === true,
    'non-SHEIN detection' => $classifier->isShein('https://example.com/cart') === false,
    'subdomain matching' => $classifier->domainMatches('m.example.com', ['example.com']) === true,
    'SHEIN embedded cart item count' => count($sampleItems) === 2,
    'SHEIN cart quantity' => ($sampleItems[0]['quantity'] ?? 0) === 2,
    'SHEIN cart currency forced to USD' => ($sampleItems[0]['currency'] ?? '') === 'USD',
    'SHEIN cart uses usdAmount' => abs((float) ($sampleItems[0]['unit_price_original'] ?? 0) - 13.29) < 0.001,
    'SHEIN cart variant' => ($sampleItems[0]['size'] ?? '') === 'M' && ($sampleItems[0]['color'] ?? '') === 'Black',
    'SHEIN escaped app-state extraction' => count($escapedItems) === 1 && ($escapedItems[0]['name'] ?? '') === 'Shoes',
    'SHEIN nested quantity extraction' => ($escapedItems[0]['quantity'] ?? 0) === 3,
    'SHEIN nested image extraction' => ($escapedItems[0]['image_url'] ?? '') === 'https://img.test/c.jpg',
    'SHEIN nested attributes extraction' => ($escapedItems[0]['color'] ?? '') === 'White' && ($escapedItems[0]['size'] ?? '') === '39',
    'SHEIN recommendations excluded' => count($recommendItems) === 1 && ($recommendItems[0]['name'] ?? '') === 'Real Cart Dress',
    'Playwright payload noise not re-added' => count($browserWithPayloadNoise) === 1 && ($browserWithPayloadNoise[0]['name'] ?? '') === 'Real Browser Cart Item',
    'Playwright item normalization' => count($browserItems) === 1 && ($browserItems[0]['name'] ?? '') === 'Browser Dress' && ($browserItems[0]['quantity'] ?? 0) === 2,
    'Playwright worker script exists' => is_file(__DIR__.'/shein-browser-import.mjs'),
    'Playwright package configured' => str_contains(file_get_contents(__DIR__.'/../package.json'), 'playwright-chromium'),
    'SHEIN browser env documented' => str_contains(file_get_contents(__DIR__.'/../.env.example'), 'SHEIN_BROWSER_HEADLESS'),
];

$failed = [];
foreach ($checks as $name => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
    if (! $ok) $failed[] = $name;
}

exit($failed ? 1 : 0);
