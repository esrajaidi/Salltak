<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Store;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'users' => User::query()->where('role', 'customer')->count(),
            'carts' => Cart::count(),
            'items' => \App\Models\CartItem::count(),
            'stores' => Store::query()->where('is_active', true)->count(),
        ];
        $latestCarts = Cart::with(['user', 'store'])->latest()->limit(8)->get();
        return view('admin.dashboard', compact('stats', 'latestCarts'));
    }
}
