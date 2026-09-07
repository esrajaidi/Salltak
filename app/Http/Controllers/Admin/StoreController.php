<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index() { return view('admin.stores.index', ['stores' => Store::latest()->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:stores,slug'],
            'domains_text' => ['required', 'string', 'max:1000'],
            'currency' => ['required', 'string', 'size:3'],
            'logo_url' => ['nullable', 'url:http,https', 'max:2000'],
            'adapter' => ['required', Rule::in(['generic', 'shein'])],
        ]);
        $store = Store::create([
            'name' => $data['name'], 'slug' => $data['slug'] ?: Str::slug($data['name']),
            'domains' => $this->domains($data['domains_text']), 'currency' => strtoupper($data['currency']),
            'logo_url' => $data['logo_url'] ?? null, 'adapter' => $data['adapter'], 'is_active' => true,
        ]);
        $this->audit->log('store.created', 'إضافة متجر/موقع', $request->user(), $store, $store->name);
        return back()->with('success', 'تمت إضافة الموقع.');
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('stores', 'slug')->ignore($store->id)],
            'domains_text' => ['required', 'string', 'max:1000'],
            'currency' => ['required', 'string', 'size:3'],
            'logo_url' => ['nullable', 'url:http,https', 'max:2000'],
            'adapter' => ['required', Rule::in(['generic', 'shein'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $store->update([
            'name' => $data['name'], 'slug' => $data['slug'], 'domains' => $this->domains($data['domains_text']),
            'currency' => strtoupper($data['currency']), 'logo_url' => $data['logo_url'] ?? null,
            'adapter' => $data['adapter'], 'is_active' => $request->boolean('is_active'),
        ]);
        $this->audit->log('store.updated', 'تحديث متجر/موقع', $request->user(), $store, $store->name);
        return back()->with('success', 'تم تحديث الموقع.');
    }

    private function domains(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($domain) => strtolower(trim(preg_replace('#^https?://#', '', trim($domain)), " /")),
            preg_split('/[,\\n]+/', $value) ?: []
        ))));
    }
}
