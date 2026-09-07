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
        $data = $request->validate(['source_url' => ['required', 'url:http,https', 'max:2000']]);
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'preview_token' => ['required', 'string', 'size:48'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.key' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
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

        $snapshotItems = collect($snapshot['items'] ?? [])->keyBy('_key');
        $selectedItems = [];
        $seenKeys = [];

        foreach ($data['items'] as $submitted) {
            $key = (string) $submitted['key'];
            if (isset($seenKeys[$key]) || ! $snapshotItems->has($key)) {
                return back()->withErrors(['items' => 'تعذر التحقق من بيانات أحد المنتجات. أعد جلب السلة وحاول من جديد.']);
            }

            $seenKeys[$key] = true;
            $item = $snapshotItems->get($key);
            $item['quantity'] = (int) $submitted['quantity'];
            $selectedItems[] = $item;
        }

        $currency = strtoupper((string) ($snapshot['source_currency'] ?? 'USD'));
        $rate = (float) ($snapshot['exchange_rate'] ?? 0);
        if ($currency !== 'LYD' && $rate <= 0) {
            return back()->withErrors(['items' => 'لا يوجد سعر صرف مفعّل للعملة المطلوبة. أعد جلب السلة بعد ضبط سعر الصرف.']);
        }

        $cart = DB::transaction(function () use ($request, $snapshot, $selectedItems, $currency, $rate) {
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
