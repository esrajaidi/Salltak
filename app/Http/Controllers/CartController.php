<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\ExchangeRate;
use App\Models\Store;
use App\Services\CartImport\CartImportService;
use App\Services\MoneyCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function __construct(
        private readonly CartImportService $importer,
        private readonly MoneyCalculator $money,
    ) {}

    public function index(Request $request)
    {
        $carts = $request->user()->carts()->with('store')->withCount('items')->latest()->paginate(12);
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

        return view('carts.preview', [
            'sourceUrl' => $data['source_url'],
            'store' => $store,
            'result' => $result,
            'currency' => $currency,
            'rate' => $rate,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_url' => ['required', 'url:http,https', 'max:2000'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'source_currency' => ['required', 'string', 'size:3'],
            'import_status' => ['required', Rule::in(['success', 'needs_review', 'failed'])],
            'import_message' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.external_id' => ['nullable', 'string', 'max:190'],
            'items.*.name' => ['required_with:items', 'string', 'max:500'],
            'items.*.product_url' => ['nullable', 'url:http,https', 'max:2000'],
            'items.*.image_url' => ['nullable', 'url:http,https', 'max:2000'],
            'items.*.variant' => ['nullable', 'string', 'max:190'],
            'items.*.color' => ['nullable', 'string', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price_original' => ['required_with:items', 'numeric', 'min:0', 'max:999999999'],
        ]);

        $currency = strtoupper($data['source_currency']);
        $rate = ExchangeRate::rateFor($currency);
        if ($currency !== 'LYD' && $rate <= 0) {
            return back()->withErrors(['source_currency' => "لا يوجد سعر صرف مفعّل للعملة {$currency}."])->withInput();
        }

        $cart = DB::transaction(function () use ($request, $data, $currency, $rate) {
            $subtotal = 0;
            foreach ($data['items'] ?? [] as $item) {
                $subtotal += $this->money->lineTotal((float) $item['unit_price_original'], (int) $item['quantity']);
            }

            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'store_id' => $data['store_id'] ?? null,
                'source_url' => $data['source_url'],
                'source_host' => strtolower((string) parse_url($data['source_url'], PHP_URL_HOST)),
                'source_currency' => $currency,
                'exchange_rate' => $rate,
                'subtotal_original' => $subtotal,
                'total_lyd' => $this->money->toLyd($subtotal, $rate),
                'status' => 'saved',
                'import_status' => $data['import_status'],
                'import_message' => $data['import_message'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $item) {
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

        return redirect()->route('carts.show', $cart)->with('success', 'تم حفظ السلة بنجاح.');
    }

    public function show(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        $cart->load(['items', 'store']);
        return view('carts.show', compact('cart'));
    }

    public function cancel(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        $cart->update(['status' => 'cancelled']);
        return back()->with('success', 'تم إلغاء السلة.');
    }

    public function destroy(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 403);
        $cart->delete();
        return redirect()->route('carts.index')->with('success', 'تم حذف السلة.');
    }
}
