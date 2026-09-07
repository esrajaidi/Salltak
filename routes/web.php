<?php

use App\Http\Controllers\Admin\CartController as AdminCartController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepositRuleController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/my-carts', [CartController::class, 'index'])->name('carts.index');
    Route::get('/my-carts/new', [CartController::class, 'create'])->name('carts.create');
    Route::post('/my-carts/analyze', [CartController::class, 'analyze'])->name('carts.analyze');
    Route::post('/my-carts', [CartController::class, 'store'])->name('carts.store');
    Route::get('/my-carts/{cart}', [CartController::class, 'show'])->name('carts.show');
    Route::patch('/my-carts/{cart}/cancel', [CartController::class, 'cancel'])->name('carts.cancel');
    Route::delete('/my-carts/{cart}', [CartController::class, 'destroy'])->name('carts.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/my-carts/{cart}/order', [OrderController::class, 'storeFromCart'])->name('orders.from-cart');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/messages', [OrderController::class, 'message'])->name('orders.messages.store');
    Route::patch('/orders/{order}/items/{item}/reply', [OrderController::class, 'respondItem'])->name('orders.items.reply');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'backoffice'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/assign', [AdminOrderController::class, 'assign'])->name('orders.assign');
    Route::patch('/orders/{order}/items/{item}/review', [AdminOrderController::class, 'reviewItem'])->name('orders.items.review');
    Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::patch('/orders/{order}/payment-terms', [AdminOrderController::class, 'updatePaymentTerms'])->name('orders.payment-terms');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/messages', [AdminOrderController::class, 'message'])->name('orders.messages.store');
    Route::post('/orders/{order}/notes', [AdminOrderController::class, 'note'])->name('orders.notes.store');
    Route::patch('/orders/{order}/payments/{payment}/verify', [AdminOrderController::class, 'verifyPayment'])->name('orders.payments.verify');

    Route::get('/carts', [AdminCartController::class, 'index'])->name('carts.index');
    Route::get('/carts/{cart}', [AdminCartController::class, 'show'])->name('carts.show');
    Route::patch('/carts/{cart}/status', [AdminCartController::class, 'updateStatus'])->name('carts.status');

    Route::middleware('admin')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');

        Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('rates.index');
        Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('rates.store');
        Route::patch('/exchange-rates/{exchangeRate}/toggle', [ExchangeRateController::class, 'toggle'])->name('rates.toggle');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::patch('/payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');
        Route::post('/payment-methods/install-libya-catalog', [PaymentMethodController::class, 'installLibyaCatalog'])->name('payment-methods.install-libya');

        Route::get('/deposit-rules', [DepositRuleController::class, 'index'])->name('deposit-rules.index');
        Route::post('/deposit-rules', [DepositRuleController::class, 'store'])->name('deposit-rules.store');
        Route::put('/deposit-rules/{depositRule}', [DepositRuleController::class, 'update'])->name('deposit-rules.update');
        Route::patch('/deposit-rules/{depositRule}/toggle', [DepositRuleController::class, 'toggle'])->name('deposit-rules.toggle');
        Route::delete('/deposit-rules/{depositRule}', [DepositRuleController::class, 'destroy'])->name('deposit-rules.destroy');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/site-content', [SiteContentController::class, 'index'])->name('site-content.index');
        Route::get('/site-content/preview', [SiteContentController::class, 'preview'])->name('site-content.preview');
        Route::post('/site-content/publish-all', [SiteContentController::class, 'publishAll'])->name('site-content.publish-all');
        Route::get('/site-content/{siteSection}/edit', [SiteContentController::class, 'edit'])->name('site-content.edit');
        Route::put('/site-content/{siteSection}', [SiteContentController::class, 'update'])->name('site-content.update');
        Route::post('/site-content/{siteSection}/publish', [SiteContentController::class, 'publish'])->name('site-content.publish');
    });
});
