<?php

namespace App\Providers;

use App\Models\SiteSection;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // The admin comparison opens two read-only SHEIN browser contexts.
        // Keep its protective rate limit, but show an actionable message
        // inside Salltak instead of a blank 429 Too Many Requests page.
        RateLimiter::for('shein-account-price-probe', function (Request $request) {
            return Limit::perMinutes(5, 2)
                ->by('shein-account-probe:'.($request->user()?->getAuthIdentifier() ?? $request->ip()))
                ->response(function (Request $request, array $headers) {
                    return redirect()->route('admin.shein-session.index')
                        ->withErrors([
                            'product_url' => 'وصلتِ للحد المسموح لاختبار سعر حساب SHEIN (مرتين خلال 5 دقائق). انتظري 5 دقائق ثم اضغطي مرة واحدة، بدون تحديث متكرر.',
                        ])
                        ->withInput($request->only('product_url'));
                });
        });

        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            $navNotifications = collect();
            $unreadNotificationCount = 0;

            if ($user && Schema::hasTable('app_notifications')) {
                $navNotifications = $user->appNotifications()->latest()->limit(6)->get();
                $unreadNotificationCount = $user->appNotifications()->whereNull('read_at')->count();
            }

            $siteFooter = [];
            if (Schema::hasTable('site_sections')) {
                if (request()->routeIs('admin.site-content.preview')) {
                    $footerSection = SiteSection::query()->where('slug', 'footer')->first();
                    if ($footerSection?->draft_is_visible) $siteFooter = $footerSection->draft_content ?? $footerSection->content ?? [];
                } else {
                    $footerSection = SiteSection::query()->where('slug', 'footer')->where('is_visible', true)->first();
                    $siteFooter = $footerSection?->content ?? [];
                }
            }

            $view->with(compact('navNotifications', 'unreadNotificationCount', 'siteFooter'));
        });
    }
}
