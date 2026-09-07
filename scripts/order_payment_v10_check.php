<?php
$root = dirname(__DIR__);
$checks = [
    'order model' => ['app/Models/Order.php', 'class Order'],
    'order item model' => ['app/Models/OrderItem.php', 'class OrderItem'],
    'payment method model' => ['app/Models/PaymentMethod.php', "'encrypted:array'"],
    'payment model' => ['app/Models/Payment.php', 'class Payment'],
    'deposit rule model' => ['app/Models/DepositRule.php', 'class DepositRule'],
    'order message model' => ['app/Models/OrderMessage.php', 'class OrderMessage'],
    'history model' => ['app/Models/OrderStatusHistory.php', 'class OrderStatusHistory'],
    'deposit calculator' => ['app/Services/DepositCalculator.php', 'class DepositCalculator'],
    'customer order controller' => ['app/Http/Controllers/OrderController.php', 'storeFromCart'],
    'customer payment controller' => ['app/Http/Controllers/PaymentController.php', 'store'],
    'backoffice middleware' => ['app/Http/Middleware/BackofficeMiddleware.php', 'isBackoffice'],
    'admin order controller' => ['app/Http/Controllers/Admin/OrderController.php', 'reviewItem'],
    'payment method admin' => ['app/Http/Controllers/Admin/PaymentMethodController.php', 'toggle'],
    'deposit rules admin' => ['app/Http/Controllers/Admin/DepositRuleController.php', 'store'],
    'customer orders view' => ['resources/views/orders/show.blade.php', 'المتبقي'],
    'admin orders view' => ['resources/views/admin/orders/show.blade.php', 'مراجعة المنتجات'],
    'payment methods view' => ['resources/views/admin/payment-methods/index.blade.php', 'طرق الدفع'],
    'deposit rules view' => ['resources/views/admin/deposit-rules/index.blade.php', 'قواعد العربون'],
    'routes include order submit' => ['routes/web.php', 'orders.from-cart'],
    'routes include payment methods' => ['routes/web.php', 'payment-methods'],
    'routes include deposit rules' => ['routes/web.php', 'deposit-rules'],
    'admin layout has orders nav' => ['resources/views/layouts/admin.blade.php', 'الطلبات'],
    'cart has request order action' => ['resources/views/carts/show.blade.php', 'اطلب هذه السلة'],
];
$failed = 0;
foreach ($checks as $label => [$file, $needle]) {
    $path = $root.'/'.$file;
    $ok = is_file($path) && str_contains((string) file_get_contents($path), $needle);
    echo ($ok ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    $failed += $ok ? 0 : 1;
}
echo PHP_EOL.(count($checks)-$failed).'/'.count($checks).' V10 checks passed'.PHP_EOL;
exit($failed ? 1 : 0);
