@auth
<div class="dropdown notification-dropdown">
    <button class="btn notification-bell-btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="الإشعارات">
        <span aria-hidden="true">🔔</span>
        @if(($unreadNotificationCount ?? 0) > 0)
            <span class="notification-count">{{ ($unreadNotificationCount ?? 0) > 99 ? '99+' : $unreadNotificationCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-menu p-0 shadow-lg border-0">
        <div class="notification-menu-head d-flex align-items-center justify-content-between gap-2">
            <div><strong>الإشعارات</strong><small>{{ $unreadNotificationCount ?? 0 }} غير مقروء</small></div>
            @if(($unreadNotificationCount ?? 0) > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')
                    <button class="btn btn-link btn-sm text-decoration-none p-0" type="submit">تعليم الكل</button>
                </form>
            @endif
        </div>
        <div class="notification-menu-list">
            @forelse(($navNotifications ?? collect()) as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification) }}" class="m-0">@csrf @method('PATCH')
                    <button type="submit" class="notification-mini-item {{ $notification->read_at ? '' : 'is-unread' }}">
                        <span class="notification-mini-icon">{{ match($notification->icon){'payment'=>'د','item'=>'▣','message'=>'✉','assignment'=>'◎','status'=>'✓','note'=>'•','customer'=>'◉','new-order'=>'＋',default=>'•'} }}</span>
                        <span class="min-w-0"><strong>{{ $notification->title }}</strong>@if($notification->body)<small>{{ $notification->body }}</small>@endif<time>{{ $notification->created_at->diffForHumans() }}</time></span>
                    </button>
                </form>
            @empty
                <div class="notification-empty">لا توجد إشعارات حتى الآن.</div>
            @endforelse
        </div>
        <a class="notification-view-all" href="{{ route('notifications.index') }}">عرض كل الإشعارات ←</a>
    </div>
</div>
@endauth
