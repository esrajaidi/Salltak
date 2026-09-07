<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->appNotifications()->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, AppNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markRead();

        return $notification->url
            ? redirect()->to($notification->url)
            : back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function readAll(Request $request)
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }
}
