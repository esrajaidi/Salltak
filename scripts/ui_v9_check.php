<?php

$root = dirname(__DIR__);
$files = [
    'css' => $root.'/public/css/app.css',
    'app' => $root.'/resources/views/layouts/app.blade.php',
    'admin' => $root.'/resources/views/layouts/admin.blade.php',
    'home' => $root.'/resources/views/home.blade.php',
    'js' => $root.'/public/js/app-ui.js',
];

$failures = [];
$read = static function (string $path): string {
    return is_file($path) ? (string) file_get_contents($path) : '';
};

$css = $read($files['css']);
$app = $read($files['app']);
$admin = $read($files['admin']);
$home = $read($files['home']);
$js = $read($files['js']);

$adminPages = [
    $root.'/resources/views/admin/carts/index.blade.php',
    $root.'/resources/views/admin/carts/show.blade.php',
    $root.'/resources/views/admin/users/index.blade.php',
    $root.'/resources/views/admin/stores/index.blade.php',
    $root.'/resources/views/admin/exchange-rates/index.blade.php',
    $root.'/resources/views/admin/settings/edit.blade.php',
];
$customerPages = [
    $root.'/resources/views/carts/create.blade.php',
    $root.'/resources/views/carts/index.blade.php',
    $root.'/resources/views/carts/preview.blade.php',
    $root.'/resources/views/carts/show.blade.php',
];

$checks = [
    'navy token' => str_contains($css, '#0F2744') || str_contains($css, '#0f2744'),
    'deep navy token' => str_contains($css, '#091827'),
    'teal token' => str_contains($css, '#14B8A6') || str_contains($css, '#14b8a6'),
    'sky token' => str_contains($css, '#38BDF8') || str_contains($css, '#38bdf8'),
    'gold token' => str_contains($css, '#F4B942') || str_contains($css, '#f4b942'),
    'reveal class' => str_contains($css, '.reveal'),
    'reduced motion' => str_contains($css, 'prefers-reduced-motion'),
    'responsive admin sidebar' => str_contains($css, '.admin-sidebar') && str_contains($css, '@media'),
    'landing hero' => str_contains($home, 'landing-hero'),
    'how it works section' => str_contains($home, 'how-it-works'),
    'platform preview section' => str_contains($home, 'platform-preview'),
    'landing cta section' => str_contains($home, 'landing-cta'),
    'admin topbar' => str_contains($admin, 'admin-topbar'),
    'admin sidebar markup' => str_contains($admin, 'admin-sidebar'),
    'app ui script loaded' => str_contains($app, 'js/app-ui.js'),
    'app ui script exists' => $js !== '',
    'admin management headers' => !array_filter($adminPages, fn($p) => !str_contains($read($p), 'admin-page-header')),
    'admin management panels' => !array_filter($adminPages, fn($p) => !str_contains($read($p), 'admin-panel')),
    'customer page shell' => !array_filter($customerPages, fn($p) => !str_contains($read($p), 'customer-page')),
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        $failures[] = $name;
    }
}

$legacy = ['#6f3fb5', '#5d2ea3', '#4b228d', '#24135f', '#251451'];
$legacyHaystack = strtolower($css."\n".$app."\n".$admin);
foreach ($legacy as $token) {
    if (str_contains($legacyHaystack, $token)) {
        $failures[] = "legacy purple token {$token}";
    }
}

if ($failures) {
    fwrite(STDERR, "UI V9 CHECK FAILED\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

echo "UI V9 CHECK PASS\n";
