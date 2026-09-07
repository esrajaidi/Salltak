<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $attentionStatuses = ['submitted','under_review','needs_customer_action','awaiting_deposit','awaiting_payment','awaiting_balance'];

        $stats = [
            'users' => User::query()->where('role', 'customer')->count(),
            'carts' => Cart::count(),
            'orders' => Order::count(),
            'pending_orders' => Order::query()->whereIn('status',$attentionStatuses)->count(),
            'pending_payments' => Payment::query()->whereIn('status',['pending_verification','pending_gateway'])->count(),
            'paid' => Payment::query()->where('status','verified')->sum('amount'),
            'stores' => Store::query()->where('is_active', true)->count(),
            'delivered' => Order::query()->where('status','delivered')->count(),
        ];

        $needsAction = Order::with(['user','assignee','cart.store'])
            ->whereIn('status',['submitted','needs_customer_action','awaiting_deposit','awaiting_payment','awaiting_balance'])
            ->latest()->limit(6)->get();

        $pendingPayments = Payment::with(['order.user','method'])
            ->whereIn('status',['pending_verification','pending_gateway'])
            ->latest()->limit(6)->get();

        $agingOrders = Order::with(['user','assignee'])
            ->whereIn('status',$attentionStatuses)
            ->where('updated_at','<',now()->subDay())
            ->oldest('updated_at')->limit(6)->get();

        $recentActivity = Schema::hasTable('audit_logs')
            ? AuditLog::with(['actor','order'])->latest('created_at')->limit(10)->get()
            : collect();
        $latestOrders = Order::with(['user','assignee','cart.store'])->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats','latestOrders','needsAction','pendingPayments','agingOrders','recentActivity'));
    }
}
