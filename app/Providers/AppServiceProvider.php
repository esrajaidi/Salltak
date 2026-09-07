<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            $navNotifications = collect();
            $unreadNotificationCount = 0;

            if ($user && Schema::hasTable('app_notifications')) {
                $navNotifications = $user->appNotifications()->latest()->limit(6)->get();
                $unreadNotificationCount = $user->appNotifications()->whereNull('read_at')->count();
            }

            $view->with(compact('navNotifications', 'unreadNotificationCount'));
        });
    }
}
