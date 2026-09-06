<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $query = Cart::with(['user', 'store'])->withCount('items')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($x) use ($q) {
                $x->where('number', 'like', "%{$q}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }
        return view('admin.carts.index', ['carts' => $query->paginate(20)->withQueryString()]);
    }

    public function show(Cart $cart)
    {
        $cart->load(['user', 'store', 'items']);
        return view('admin.carts.show', compact('cart'));
    }

    public function updateStatus(Request $request, Cart $cart)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'saved', 'confirmed', 'cancelled'])]]);
        $cart->update($data);
        return back()->with('success', 'تم تحديث حالة السلة.');
    }
}
