<?php
$root = dirname(__DIR__);
$checks = [
    'SiteSection model' => 'app/Models/SiteSection.php',
    'Site content controller' => 'app/Http/Controllers/Admin/SiteContentController.php',
    'CMS migration' => 'database/migrations/2026_09_07_224500_create_site_sections_table.php',
    'CMS admin index' => 'resources/views/admin/site-content/index.blade.php',
    'CMS admin edit' => 'resources/views/admin/site-content/edit.blade.php',
    'Dynamic homepage' => 'resources/views/home.blade.php',
    'Hero partial' => 'resources/views/site/sections/hero.blade.php',
    'FAQ partial' => 'resources/views/site/sections/faq.blade.php',
];
$fail = 0;
foreach ($checks as $label => $path) {
    $ok = is_file($root.'/'.$path);
    echo ($ok ? '[PASS] ' : '[FAIL] ').$label."\n";
    if (!$ok) $fail++;
}
$routes = @file_get_contents($root.'/routes/web.php') ?: '';
foreach (["site-content.index", "SiteContentController"] as $needle) {
    $ok = str_contains($routes, $needle);
    echo ($ok ? '[PASS] ' : '[FAIL] ')."route marker: $needle\n";
    if (!$ok) $fail++;
}
$homeController = @file_get_contents($root.'/app/Http/Controllers/HomeController.php') ?: '';
$cmsController = @file_get_contents($root.'/app/Http/Controllers/Admin/SiteContentController.php') ?: '';
$layout = @file_get_contents($root.'/resources/views/layouts/app.blade.php') ?: '';
$adminLayout = @file_get_contents($root.'/resources/views/layouts/admin.blade.php') ?: '';
$migration = @file_get_contents($root.'/database/migrations/2026_09_07_224500_create_site_sections_table.php') ?: '';
$hero = @file_get_contents($root.'/resources/views/site/sections/hero.blade.php') ?: '';
$payments = @file_get_contents($root.'/resources/views/site/sections/payments.blade.php') ?: '';
$edit = @file_get_contents($root.'/resources/views/admin/site-content/edit.blade.php') ?: '';
$ok = str_contains($homeController, 'SiteSection');
echo ($ok ? '[PASS] ' : '[FAIL] ')."HomeController loads CMS\n";
if (!$ok) $fail++;

$extraChecks = [
    'draft and published JSON are separate' => str_contains($migration, "'content'") && str_contains($migration, "'draft_content'"),
    'draft visibility is separate' => str_contains($migration, 'draft_is_visible'),
    'single-section publishing exists' => str_contains($cmsController, 'function publish('),
    'publish-all exists' => str_contains($cmsController, 'function publishAll('),
    'preview uses draft content' => str_contains($cmsController, 'draft_content') && str_contains($cmsController, "with('isPreview', true)"),
    'image upload uses public disk' => str_contains($cmsController, "store('site-content', 'public')"),
    'CMS is in admin navigation' => str_contains($adminLayout, 'إدارة الموقع الخارجي'),
    'public SEO stack exists' => str_contains($layout, "@stack('meta')"),
    'hero supports uploaded images' => str_contains($hero, "asset('storage/"),
    'payment showcase uses active configured methods' => str_contains($homeController, 'isConfiguredForActivation') && str_contains($payments, 'paymentMethods'),
    'admin editor is not raw JSON' => !str_contains($edit, 'name="json"') && str_contains($edit, 'العنوان الرئيسي'),
];
foreach ($extraChecks as $label => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$label."\n";
    if (!$ok) $fail++;
}

exit($fail > 0 ? 1 : 0);
