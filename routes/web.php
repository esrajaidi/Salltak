<?php

use App\Http\Controllers\Admin\CartController as AdminCartController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
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
    Route::get('/my-carts', [CartController::class, 'index'])->name('carts.index');
    Route::get('/my-carts/new', [CartController::class, 'create'])->name('carts.create');
    Route::post('/my-carts/analyze', [CartController::class, 'analyze'])->name('carts.analyze');
    Route::post('/my-carts', [CartController::class, 'store'])->name('carts.store');
    Route::get('/my-carts/{cart}', [CartController::class, 'show'])->name('carts.show');
    Route::patch('/my-carts/{cart}/cancel', [CartController::class, 'cancel'])->name('carts.cancel');
    Route::delete('/my-carts/{cart}', [CartController::class, 'destroy'])->name('carts.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/carts', [AdminCartController::class, 'index'])->name('carts.index');
    Route::get('/carts/{cart}', [AdminCartController::class, 'show'])->name('carts.show');
    Route::patch('/carts/{cart}/status', [AdminCartController::class, 'updateStatus'])->name('carts.status');

    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
    Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('rates.index');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('rates.store');
    Route::patch('/exchange-rates/{exchangeRate}/toggle', [ExchangeRateController::class, 'toggle'])->name('rates.toggle');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
