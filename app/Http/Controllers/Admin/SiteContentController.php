<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\SiteSection;
use App\Models\Store;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteContentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index()
    {
        $sections = SiteSection::query()->orderBy('draft_sort_order')->orderBy('id')->get();
        $stats = [
            'total'=>$sections->count(),
            'visible'=>$sections->where('draft_is_visible', true)->count(),
            'drafts'=>$sections->filter->hasDraftChanges()->count(),
            'published'=>$sections->whereNotNull('published_at')->count(),
        ];

        return view('admin.site-content.index', compact('sections','stats'));
    }

    public function edit(SiteSection $siteSection)
    {
        $content = $siteSection->draft_content ?? $siteSection->content ?? [];
        $iconOptions = $this->iconOptions();
        return view('admin.site-content.edit', compact('siteSection','content','iconOptions'));
    }

    public function update(Request $request, SiteSection $siteSection)
    {
        $rules = [
            'sort_order'=>['required','integer','min:0','max:9999'],
            'kicker'=>['nullable','string','max:160'], 'title'=>['nullable','string','max:300'],
            'accent'=>['nullable','string','max:220'], 'subtitle'=>['nullable','string','max:600'],
            'description'=>['nullable','string','max:2500'], 'slogan'=>['nullable','string','max:300'],
            'primary_button_text'=>['nullable','string','max:100'], 'primary_button_url'=>['nullable','string','max:500','regex:/^(\/|#|https?:\/\/)/i'],
            'secondary_button_text'=>['nullable','string','max:100'], 'secondary_button_url'=>['nullable','string','max:500','regex:/^(\/|#|https?:\/\/)/i'],
            'button_text'=>['nullable','string','max:100'], 'button_url'=>['nullable','string','max:500','regex:/^(\/|#|https?:\/\/)/i'],
            'phone'=>['nullable','string','max:80'], 'email'=>['nullable','email','max:190'],
            'whatsapp'=>['nullable','string','max:80'], 'address'=>['nullable','string','max:400'],
            'meta_title'=>['nullable','string','max:190'], 'meta_description'=>['nullable','string','max:500'],
            'og_title'=>['nullable','string','max:190'], 'og_description'=>['nullable','string','max:500'],
            'badges'=>['nullable','array','max:8'], 'badges.*'=>['nullable','string','max:120'],
            'bullets'=>['nullable','array','max:10'], 'bullets.*'=>['nullable','string','max:180'],
            'items'=>['nullable','array','max:12'],
            'items.*.icon'=>['nullable','string',Rule::in(array_keys($this->iconOptions()))],
            'items.*.title'=>['nullable','string','max:180'], 'items.*.description'=>['nullable','string','max:800'],
            'items.*.name'=>['nullable','string','max:120'], 'items.*.city'=>['nullable','string','max:120'],
            'items.*.quote'=>['nullable','string','max:1200'],
            'items.*.question'=>['nullable','string','max:300'], 'items.*.answer'=>['nullable','string','max:1600'],
            'image_desktop'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'image_mobile'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'image'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'og_image'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
        ];
        $validated = $request->validate($rules);
        $current = $siteSection->draft_content ?? $siteSection->content ?? [];
        $allowed = $this->allowedContentKeys($siteSection->slug);
        $payload = $current;

        foreach ($allowed as $key) {
            if (in_array($key, ['image_desktop','image_mobile','image','og_image'], true)) continue;
            if (array_key_exists($key, $validated)) $payload[$key] = $validated[$key];
        }

        if (in_array('badges', $allowed, true)) {
            $payload['badges'] = $this->cleanStrings($validated['badges'] ?? []);
        }
        if (in_array('bullets', $allowed, true)) {
            $payload['bullets'] = $this->cleanStrings($validated['bullets'] ?? []);
        }
        if (in_array('items', $allowed, true)) {
            $payload['items'] = $this->cleanItems($validated['items'] ?? []);
        }

        foreach (['image_desktop','image_mobile','image','og_image'] as $field) {
            if (! in_array($field, $allowed, true) || ! $request->hasFile($field)) continue;
            $payload[$field] = $request->file($field)->store('site-content', 'public');
        }

        $siteSection->update([
            'draft_content'=>$payload,
            'draft_is_visible'=>$request->boolean('is_visible'),
            'draft_sort_order'=>(int) $validated['sort_order'],
        ]);

        $this->audit->log('site_content.draft_saved', 'حفظ مسودة قسم الموقع الخارجي: '.$siteSection->label, $request->user(), $siteSection);
        return back()->with('success', 'تم حفظ المسودة. لن تظهر للزوار حتى تضغطي نشر.');
    }

    public function publish(Request $request, SiteSection $siteSection)
    {
        $siteSection->publish();
        $this->audit->log('site_content.published', 'نشر قسم الموقع الخارجي: '.$siteSection->label, $request->user(), $siteSection);
        return back()->with('success', 'تم نشر القسم وأصبح ظاهرًا للزوار حسب حالة الإظهار.');
    }

    public function publishAll(Request $request)
    {
        $sections = SiteSection::query()->get();
        foreach ($sections as $section) $section->publish();
        $this->audit->log('site_content.publish_all', 'نشر جميع تعديلات الموقع الخارجي', $request->user());
        return back()->with('success', 'تم نشر جميع تعديلات الموقع الخارجي.');
    }

    public function preview()
    {
        $sections = SiteSection::query()->orderBy('draft_sort_order')->get()->map(function (SiteSection $section) {
            $copy = clone $section;
            $copy->content = $section->draft_content ?? $section->content ?? [];
            $copy->is_visible = $section->draft_is_visible;
            $copy->sort_order = $section->draft_sort_order;
            return $copy;
        })->filter(fn ($section) => $section->is_visible)->sortBy('sort_order')->values();

        $stores = Store::query()->where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->filter(fn (PaymentMethod $method) => $method->isConfiguredForActivation())->values();
        $seoSection = SiteSection::query()->where('slug','seo')->first();
        $seo = $seoSection?->draft_content ?? $seoSection?->content ?? [];

        return view('home', compact('sections','stores','paymentMethods','seo'))->with('isPreview', true);
    }

    private function cleanStrings(array $values): array
    {
        return collect($values)->map(fn ($v)=>trim((string)$v))->filter()->values()->all();
    }

    private function cleanItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            return collect((array)$item)->map(fn ($v)=>is_string($v) ? trim($v) : $v)->filter(fn ($v)=>$v !== '' && $v !== null)->all();
        })->filter(function ($item) {
            $meaningful = collect($item)->except('icon')->filter(fn ($v)=>$v !== '' && $v !== null);
            return $meaningful->isNotEmpty();
        })->values()->all();
    }

    private function allowedContentKeys(string $slug): array
    {
        return match ($slug) {
            'hero' => ['kicker','title','accent','description','primary_button_text','primary_button_url','secondary_button_text','secondary_button_url','badges','image_desktop','image_mobile'],
            'how_it_works' => ['kicker','title','subtitle','items'],
            'stores' => ['kicker','title','subtitle'],
            'showcase' => ['kicker','title','description','bullets','image_desktop','image_mobile'],
            'features' => ['kicker','title','subtitle','items'],
            'payments' => ['kicker','title','subtitle'],
            'testimonials' => ['kicker','title','subtitle','items','image'],
            'faq' => ['kicker','title','subtitle','items'],
            'cta' => ['kicker','title','description','button_text','button_url','image'],
            'footer' => ['slogan','description','phone','email','whatsapp','address'],
            'seo' => ['meta_title','meta_description','og_title','og_description','og_image'],
            default => ['kicker','title','subtitle','description'],
        };
    }

    private function iconOptions(): array
    {
        return [
            'globe'=>'العالم','box'=>'منتج','exchange'=>'تحويل','check'=>'صح','wallet'=>'محفظة',
            'activity'=>'متابعة','bell'=>'إشعار','payment'=>'دفع','orders'=>'طلب','carts'=>'سلة',
            'users'=>'عملاء','cash'=>'نقدي','message'=>'رسالة','mail'=>'بريد','clock'=>'وقت',
        ];
    }
}
