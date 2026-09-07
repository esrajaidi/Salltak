<?php
$root = dirname(__DIR__);
$checks = [];
function check(bool $condition, string $label): void {
    global $checks;
    $checks[] = [$condition, $label];
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
}
$cartMigration = file_get_contents($root.'/database/migrations/2026_08_28_100300_create_cart_items_table.php');
$orderMigration = file_get_contents($root.'/database/migrations/2026_09_07_090100_create_order_items_table.php');
check(str_contains($cartMigration, "\$table->text('name')"), 'Fresh cart_items schema stores long product names as TEXT');
check(str_contains($orderMigration, "\$table->text('name')"), 'Fresh order_items schema stores long product names as TEXT');
$upgrade = glob($root.'/database/migrations/*expand_product_name_columns*.php');
check(count($upgrade) === 1, 'Existing MySQL databases receive a product-name expansion migration');
if ($upgrade) {
    $body = file_get_contents($upgrade[0]);
    check(str_contains($body, "Schema::table('cart_items'"), 'Upgrade migration alters cart_items');
    check(str_contains($body, "Schema::table('order_items'"), 'Upgrade migration alters order_items');
    check(substr_count($body, "->text('name')->change()") >= 2, 'Upgrade migration changes both name columns to TEXT');
}
$failed = array_filter($checks, fn($c) => !$c[0]);
exit($failed ? 1 : 0);
