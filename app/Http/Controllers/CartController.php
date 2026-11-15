<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ExchangeRate;
use App\Models\Store;
use App\Services\CartImport\CartImportService;
use App\Services\MoneyCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(
        private readonly CartImportService $importer,
        private readonly MoneyCalculator $money,
    ) {}

    public function index(Request $request)
    {
        $carts = $request->user()->carts()->with(['store','order'])->withCount('items')->latest()->paginate(12);
        return view('carts.index', compact('carts'));
    }

    public function create()
    {
        $stores = Store::query()->where('is_active', true)->orderBy('name')->get();
        return view('carts.create', compact('stores'));
    }

    public function analyze(Request $request)
    {
        $rawSource = (string) $request->input('source_url', '');
        $normalizedSource = $this->extractSharedUrl($rawSource) ?? $rawSource;
        $request->merge(['source_url' => $normalizedSource]);

        $data = $request->validate(['source_url' => ['required', 'url:http,https', 'max:2000']]);

        // The local PHP built-in server often has max_execution_time=30,
        // while Playwright can legitimately take longer to load SHEIN's
        // shared cart. Give this specific request time to complete or return
        // a controlled timeout. Other routes keep their normal PHP limits.
        $host = strtolower((string) parse_url($data['source_url'], PHP_URL_HOST));
        if (($host === 'shein.com' || str_ends_with($host, '.shein.com'))
            && function_exists('set_time_limit')) {
            $importBudget = max(30, min(150, (int) config('services.cart_import.shein_browser.total_budget_seconds', 75)));
            set_time_limit($importBudget + 45);
        }

        $import = $this->importer->import($data['source_url']);
        $result = $import['result'];
        $store = $import['store'];
        $currency = strtoupper($result->currency ?: $store?->currency ?: 'USD');
        $rate = ExchangeRate::rateFor($currency);

        $previewToken = Str::random(48);
        $previewItems = collect($result->items)
            ->take(100)
            ->map(function (array $item): array {
                return [
                    '_key' => (string) Str::uuid(),
                    'external_id' => $item['external_id'] ?? null,
                    'name' => (string) ($item['name'] ?? 'منتج'),
                    'product_url' => $item['product_url'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'variant' => $item['variant'] ?? null,
                    'color' => $item['color'] ?? null,
                    'size' => $item['size'] ?? null,
                    'quantity' => max(1, min(999, (int) ($item['quantity'] ?? 1))),
                    'unit_price_original' => max(0, (float) ($item['unit_price_original'] ?? 0)),
                ];
            })
            ->values()
            ->all();

        $request->session()->put('cart_import_preview.'.$previewToken, [
            'user_id' => $request->user()->id,
            'source_url' => $data['source_url'],
            'store_id' => $store?->id,
            'source_currency' => $currency,
            'exchange_rate' => $rate,
            'import_status' => $result->status,
            'import_message' => $result->message,
            'items' => $previewItems,
            'created_at' => now()->timestamp,
        ]);

        return view('carts.preview', [
            'sourceUrl' => $data['source_url'],
            'store' => $store,
            'result' => $result,
            'currency' => $currency,
            'rate' => $rate,
            'previewToken' => $previewToken,
            'previewItems' => $previewItems,
        ]);
    }

    private function extractSharedUrl(string $value): ?string
    {
        $clean = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}]/u', '', trim($value)) ?? trim($value);

        if (! preg_match('~https://[^\s<>"\']+~iu', $clean, $matches)) {
            return null;
        }

        return rtrim($matches[0], " \t\n\r\0\x0B.,،؛;!؟)]}>'\"");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'preview_token' => ['required', 'string', 'size:48'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.key' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            // These are customer-supplied comparison prices from the SHEIN
            // app after coupon, NEVER authoritative payable/imported prices.
            'items.*.checkout_unit_price' => ['nullable', 'regex:/^(?:0|[1-9]\d{0,5})(?:\.\d{1,2})?$/D'],
            'checkout_total_from_app' => ['nullable', 'regex:/^(?:0|[1-9]\d{0,5})(?:\.\d{1,2})?$/D'],
        ]);

        $sessionKey = 'cart_import_preview.'.$data['preview_token'];
        $snapshot = $request->session()->get($sessionKey);

        if (! is_array($snapshot)
            || (int) ($snapshot['user_id'] ?? 0) !== (int) $request->user()->id
            || (int) ($snapshot['created_at'] ?? 0) < now()->subMinutes(30)->timestamp) {
            $request->session()->forget($sessionKey);
            return redirect()->route('carts.create')
                ->withErrors(['source_url' => 'انتهت جلسة مراجعة السلة. أعد جلب السلة من الرابط.']);
        }

        $sourceHost = strtolower((string) parse_url((string) ($snapshot['source_url'] ?? ''), PHP_URL_HOST));
        $isSheinSource = $sourceHost === 'shein.com' || str_ends_with($sourceHost, '.shein.com');
        if (! $isSheinSource && (
            $request->filled('checkout_total_from_app')
            || collect($data['items'])->contains(fn (array $item) =>
                isset($item['checkout_unit_price']) && $item['checkout_unit_price'] !== ''
            )
        )) {
            return back()->withErrors([
                'checkout_total_from_app' => 'مقارنة سعر التطبيق بعد الكوبون متاحة لسلات SHEIN فقط.',
            ])->withInput();
        }

        $snapshotItems = collect($snapshot['items'] ?? [])->keyBy('_key');
        $selectedItems = [];
        $seenKeys = [];
        $checkoutQuoteTotal = 0;
        $checkoutEditedItems = 0;

        foreach ($data['items'] as $submitted) {
            $key = (string) $submitted['key'];
            if (isset($seenKeys[$key]) || ! $snapshotItems->has($key)) {
                return back()->withErrors(['items' => 'تعذر التحقق من بيانات أحد المنتجات. أعد جلب السلة وحاول من جديد.']);
            }

            $seenKeys[$key] = true;
            $item = $snapshotItems->get($key);
            $item['quantity'] = (int) $submitted['quantity'];
            // Keep imported price immutable; save the customer's after-coupon
            // mobile price as a separate, unverified comparison field.
            $requestedPrice = $submitted['checkout_unit_price'] ?? null;
            if ($isSheinSource && $requestedPrice !== null && $requestedPrice !== '') {
                $item['phone_checkout_unit_price'] = round((float) $requestedPrice, 2);
                $checkoutEditedItems++;
            }
            $checkoutQuoteTotal += $this->money->lineTotal(
                (float) ($item['phone_checkout_unit_price'] ?? $item['unit_price_original']),
                $item['quantity']
            );
            $selectedItems[] = $item;
        }

        $currency = strtoupper((string) ($snapshot['source_currency'] ?? 'USD'));
        $rate = (float) ($snapshot['exchange_rate'] ?? 0);
        if ($currency !== 'LYD' && $rate <= 0) {
            return back()->withErrors(['items' => 'لا يوجد سعر صرف مفعّل للعملة المطلوبة. أعد جلب السلة بعد ضبط سعر الصرف.']);
        }

        $phoneCheckoutTotal = $isSheinSource && isset($data['checkout_total_from_app'])
            ? round((float) $data['checkout_total_from_app'], 2) : null;
        $hasPhoneComparison = $isSheinSource && ($checkoutEditedItems > 0 || $phoneCheckoutTotal !== null);

        $cart = DB::transaction(function () use (
            $request, $snapshot, $selectedItems, $currency, $rate,
            $hasPhoneComparison, $phoneCheckoutTotal, $checkoutQuoteTotal, $checkoutEditedItems
        ) {
            $subtotal = 0;
            foreach ($selectedItems as $item) {
                $subtotal += $this->money->lineTotal((float) $item['unit_price_original'], (int) $item['quantity']);
            }

            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'store_id' => $snapshot['store_id'] ?? null,
                'source_url' => $snapshot['source_url'],
                'source_host' => strtolower((string) parse_url($snapshot['source_url'], PHP_URL_HOST)),
                'source_currency' => $currency,
                'exchange_rate' => $rate,
                'subtotal_original' => $subtotal,
                'total_lyd' => $this->money->toLyd($subtotal, $rate),
                'status' => 'saved',
                'import_status' => $snapshot['import_status'] ?? 'needs_review',
                'import_message' => $snapshot['import_message'] ?? null,
                'import_meta' => $hasPhoneComparison ? [
                    'phone_checkout_source' => 'customer_manually_entered',
                    'phone_checkout_total' => $phoneCheckoutTotal,
                    'phone_checkout_comparison_total' => round($checkoutQuoteTotal, 2),
                    'phone_checkout_edited_items' => $checkoutEditedItems,
                    'phone_checkout_item_count' => count($selectedItems),
                    'phone_checkout_verified' => false,
                    'requires_manager_price_review' => true,
                ] : null,
            ]);

            foreach ($selectedItems as $item) {
                $line = $this->money->lineTotal((float) $item['unit_price_original'], (int) $item['quantity']);
                $cart->items()->create([
                    'external_id' => $item['external_id'] ?? null,
                    'name' => $item['name'],
                    'product_url' => $item['product_url'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'variant' => $item['variant'] ?? null,
                    'color' => $item['color'] ?? null,
                    'size' => $item['size'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price_original' => $item['unit_price_original'],
                    'line_total_original' => $line,
                    'currency' => $currency,
                    'meta' => array_key_exists('phone_checkout_unit_price', $item) ? [
                        'phone_checkout_unit_price' => $item['phone_checkout_unit_price'],
                        'phone_checkout_price_source' => 'customer_manually_entered',
                        'phone_checkout_verified' => false,
                    ] : null,
                ]);
            }

            return $cart;
        });

        $request->session()->forget($sessionKey);

        return redirect()->route('carts.show', $cart)->with('success', 'تم حفظ السلة بنجاح.');
    }

    public function show(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        $cart->load(['store','order'])->loadCount('items');
        $itemsPage = $cart->items()->orderBy('id')->paginate(12, ['*'], 'items_page')->withQueryString();
        return view('carts.show', compact('cart', 'itemsPage'));
    }

    public function cancel(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        if ($cart->order()->exists() || $cart->status === 'submitted') {
            return back()->withErrors(['cart' => 'لا يمكن إلغاء سلة تم إرسالها كطلب. يمكنك إدارة الطلب المرتبط بها من صفحة الطلب.']);
        }
        $cart->update(['status' => 'cancelled']);
        return back()->with('success', 'تم إلغاء السلة.');
    }

    public function destroy(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        if ($cart->order()->exists() || $cart->status === 'submitted') {
            return back()->withErrors(['cart' => 'لا يمكن حذف سلة تم إرسالها كطلب، لأن سجل الطلب يجب أن يبقى محفوظًا.']);
        }
        $cart->delete();
        return redirect()->route('carts.index')->with('success', 'تم حذف السلة.');
    }
}
