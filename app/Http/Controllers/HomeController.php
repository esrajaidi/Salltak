<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\SiteSection;
use App\Models\Store;
use App\Support\SiteContentDefaults;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = $this->publishedSections();
        $stores = Store::query()->where('is_active', true)->orderBy('name')->get();
        $paymentMethods = Schema::hasTable('payment_methods')
            ? PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->get()
                ->filter(fn (PaymentMethod $method) => $method->isConfiguredForActivation())->values()
            : collect();
        $seo = SiteContentDefaults::bySlug('seo')['content'];
        if (Schema::hasTable('site_sections')) {
            $seoSection = SiteSection::query()->where('slug', 'seo')->first();
            $seo = $seoSection?->content ?? $seo;
        }

        return view('home', compact('sections', 'stores', 'paymentMethods', 'seo'));
    }

    private function publishedSections()
    {
        if (Schema::hasTable('site_sections')) {
            $sections = SiteSection::query()->where('is_visible', true)->orderBy('sort_order')->orderBy('id')->get();
            if ($sections->isNotEmpty()) return $sections;
        }

        return collect(SiteContentDefaults::sections())
            ->filter(fn ($section) => $section['is_visible'] && $section['slug'] !== 'seo')
            ->sortBy('sort_order')
            ->map(fn ($section) => (object) $section)
            ->values();
    }
}
