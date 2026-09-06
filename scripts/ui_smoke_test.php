<?php

$root = dirname(__DIR__);
$checks = [
    'layout has RTL html' => str_contains(file_get_contents($root.'/resources/views/layouts/app.blade.php'), 'dir="rtl"'),
    'layout loads local Bootstrap 5' => str_contains(file_get_contents($root.'/resources/views/layouts/app.blade.php'), "vendor/bootstrap/bootstrap.min.css"),
    'layout loads Bootstrap RTL build' => str_contains(file_get_contents($root.'/resources/views/layouts/app.blade.php'), 'bootstrap.rtl.min.css'),
    'layout loads local Bootstrap bundle' => str_contains(file_get_contents($root.'/resources/views/layouts/app.blade.php'), "vendor/bootstrap/bootstrap.bundle.min.js"),
    'admin uses responsive offcanvas' => str_contains(file_get_contents($root.'/resources/views/layouts/admin.blade.php'), 'offcanvas-lg offcanvas-start'),
    'cart tables are responsive' => str_contains(file_get_contents($root.'/resources/views/carts/show.blade.php'), 'table-responsive'),
    'preview uses responsive product editor' => str_contains(file_get_contents($root.'/resources/views/carts/preview.blade.php'), 'col-6 col-md-2'),
    'bootstrap pagination enabled' => str_contains(file_get_contents($root.'/app/Providers/AppServiceProvider.php'), 'useBootstrapFive'),
    'local Bootstrap CSS exists' => is_file($root.'/public/vendor/bootstrap/bootstrap.min.css') && filesize($root.'/public/vendor/bootstrap/bootstrap.min.css') > 100000,
    'local Bootstrap JS exists' => is_file($root.'/public/vendor/bootstrap/bootstrap.bundle.min.js') && filesize($root.'/public/vendor/bootstrap/bootstrap.bundle.min.js') > 50000,
];

$failed = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
    if (!$ok) $failed++;
}

echo PHP_EOL.(count($checks)-$failed).'/'.count($checks).' UI checks passed'.PHP_EOL;
exit($failed === 0 ? 0 : 1);
