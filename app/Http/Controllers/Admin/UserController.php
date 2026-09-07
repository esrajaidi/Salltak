<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = User::query()->whereIn('role', ['customer','order_manager'])->withCount(['carts','orders'])->latest();
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
        }
        return view('admin.users.index', ['users' => $query->paginate(20)->withQueryString()]);
    }

    public function toggle(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 422);
        $user->update(['is_active' => ! $user->is_active]);
        $this->audit->log('user.toggled', $user->is_active ? 'تفعيل مستخدم' : 'إيقاف مستخدم', $request->user(), $user, $user->email);
        return back()->with('success', 'تم تحديث حالة المستخدم.');
    }

    public function updateRole(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 422);
        $data = $request->validate(['role'=>['required', Rule::in(['customer','order_manager'])]]);
        $previous = $user->role;
        $user->update($data);
        $this->audit->log('user.role_changed', 'تغيير دور مستخدم', $request->user(), $user, $previous.' → '.$user->role);
        return back()->with('success','تم تحديث دور المستخدم.');
    }
}
