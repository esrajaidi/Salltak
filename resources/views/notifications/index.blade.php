@extends('layouts.app')
@section('title','الإشعارات')
@section('body')
<section class="page-section customer-page">
<div class="container">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4 reveal is-visible">
        <div><div class="page-kicker">مركز المتابعة</div><h1 class="page-heading mb-1">الإشعارات</h1><p class="page-subtitle mb-0">كل تحديث مهم على طلباتك ومدفوعاتك في مكان واحد.</p></div>
        @if(auth()->user()->appNotifications()->whereNull('read_at')->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-ghost" type="submit">تعليم الكل كمقروء</button></form>
        @endif
    </div>
    <div class="surface-card-elevated notification-inbox reveal is-visible">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('notifications.read',$notification) }}" class="notification-inbox-row {{ $notification->read_at ? '' : 'is-unread' }}">@csrf @method('PATCH')
                <button class="notification-inbox-action" type="submit">
                    <span class="notification-inbox-icon">{{ match($notification->icon){'payment'=>'د','item'=>'▣','message'=>'✉','assignment'=>'◎','status'=>'✓','note'=>'•','customer'=>'◉','new-order'=>'＋',default=>'•'} }}</span>
                    <span class="flex-grow-1 min-w-0"><strong>{{ $notification->title }}</strong>@if($notification->body)<span>{{ $notification->body }}</span>@endif<small>{{ $notification->created_at->format('Y-m-d H:i') }}</small></span>
                    <span class="notification-open">فتح ←</span>
                </button>
            </form>
        @empty
            <div class="notification-empty py-5">لا توجد إشعارات حتى الآن.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
</section>
@endsection
