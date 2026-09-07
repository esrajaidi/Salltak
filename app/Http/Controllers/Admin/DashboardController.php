<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'users' => User::query()->where('role', 'customer')->count(),
            'carts' => Cart::count(),
            'orders' => Order::count(),
            'pending_orders' => Order::query()->whereIn('status',['submitted','under_review','needs_customer_action','awaiting_deposit','awaiting_payment'])->count(),
            'pending_payments' => Payment::query()->whereIn('status',['pending_verification','pending_gateway'])->count(),
            'paid' => Payment::query()->where('status','verified')->sum('amount'),
            'stores' => Store::query()->where('is_active', true)->count(),
        ];
        $latestOrders = Order::with(['user','assignee','cart.store'])->latest()->limit(8)->get();
        return view('admin.dashboard', compact('stats','latestOrders'));
    }
}
