<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->where('role', 'customer')->withCount('carts')->latest();
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
        }
        return view('admin.users.index', ['users' => $query->paginate(20)->withQueryString()]);
    }

    public function toggle(User $user)
    {
        abort_if($user->isAdmin(), 422);
        $user->update(['is_active' => ! $user->is_active]);
        return back()->with('success', 'تم تحديث حالة المستخدم.');
    }
}
