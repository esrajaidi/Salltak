<?php
$root = dirname(__DIR__);
$layout = file_get_contents($root.'/resources/views/layouts/app.blade.php');
$preview = file_get_contents($root.'/resources/views/carts/preview.blade.php');
$show = file_get_contents($root.'/resources/views/carts/show.blade.php');
$create = file_get_contents($root.'/resources/views/carts/create.blade.php');
$css = file_get_contents($root.'/public/css/app.css');
$env = file_get_contents($root.'/.env.example');

$checks = [
    'Bootstrap 5 CSS loaded' => str_contains($layout, 'bootstrap.min.css'),
    'Bootstrap 5 bundle loaded' => str_contains($layout, 'bootstrap.bundle.min.js'),
    'RTL document enabled' => str_contains($layout, 'dir="rtl"'),
    'Responsive cart product cards' => str_contains($preview, 'product-editor') && str_contains($preview, 'col-12 col-lg-5 col-xl-4'),
    'Original + LYD unit price side-by-side' => str_contains($preview, 'unit-lyd') && str_contains($preview, 'price-pair'),
    'Quantity +/- controls present' => str_contains($preview, 'qty-minus') && str_contains($preview, 'qty-plus'),
    'Live per-item LYD totals present' => str_contains($preview, 'line-total-lyd') && str_contains($preview, 'price * rate'),
    'Live cart LYD total present' => str_contains($preview, 'subtotal * rate'),
    'Saved cart shows LYD per item' => str_contains($show, '$unitLyd') && str_contains($show, '$lineLyd'),
    'Analyze loading state present' => str_contains($create, 'جاري جلب السلة'),
    'Mobile quantity control responsive' => str_contains($css, '@media (max-width:575.98px)') && str_contains($css, '.quantity-control{width:100%}'),
    'Admin offcanvas styling preserved' => str_contains($css, '.admin-sidebar'),
    'Headless browser default enabled' => str_contains($env, 'SHEIN_BROWSER_HEADLESS=true'),
    'File cache defaults for local setup' => str_contains($env, 'CACHE_STORE=file') && str_contains($env, 'SESSION_DRIVER=file'),
];

$failed = [];
foreach ($checks as $name => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
    if (!$ok) $failed[] = $name;
}
echo sprintf("\n%d/%d checks passed.\n", count($checks)-count($failed), count($checks));
exit($failed ? 1 : 0);
