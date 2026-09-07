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
        $query = Cart::with(['user', 'store', 'order'])->withCount('items')->latest();
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
        $cart->load(['user', 'store', 'order'])->loadCount('items');
        $itemsPage = $cart->items()->orderBy('id')->paginate(12, ['*'], 'items_page')->withQueryString();
        return view('admin.carts.show', compact('cart', 'itemsPage'));
    }

    public function updateStatus(Request $request, Cart $cart)
    {
        if ($cart->order()->exists()) {
            return back()->withErrors(['status' => 'حالة هذه السلة مرتبطة بطلب، لذلك تُدار مراحلها من صفحة الطلب ولا يمكن إعادتها إلى حالة محفوظة.']);
        }
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'saved', 'submitted', 'confirmed', 'cancelled'])]]);
        $cart->update($data);
        return back()->with('success', 'تم تحديث حالة السلة.');
    }
}
