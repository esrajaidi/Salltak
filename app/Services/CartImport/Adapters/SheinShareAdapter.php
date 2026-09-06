<?php

namespace App\Services\CartImport\Adapters;

use App\Models\Store;
use App\Services\CartImport\Contracts\CartSourceAdapter;
use App\Services\CartImport\ImportResult;
use App\Services\CartImport\Browser\SheinBrowserImporter;
use App\Services\StoreUrlClassifier;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SheinShareAdapter implements CartSourceAdapter
{
    private const COUNTRY_CURRENCY = [
        'AE' => 'AED', 'SA' => 'SAR', 'KW' => 'KWD', 'QA' => 'QAR', 'BH' => 'BHD', 'OM' => 'OMR',
        'US' => 'USD', 'GB' => 'GBP', 'UK' => 'GBP', 'EU' => 'EUR', 'FR' => 'EUR', 'DE' => 'EUR', 'IT' => 'EUR',
        'ES' => 'EUR', 'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'IE' => 'EUR', 'PT' => 'EUR', 'GR' => 'EUR',
        'TR' => 'TRY', 'CA' => 'CAD', 'AU' => 'AUD', 'JP' => 'JPY', 'BR' => 'BRL', 'ZA' => 'ZAR',
    ];

    public function __construct(
        private readonly StoreUrlClassifier $classifier,
        private readonly SheinBrowserImporter $browser,
    ) {}

    public function canHandle(string $url, ?Store $store = null): bool
    {
        return $this->classifier->isShein($url);
    }

    public function import(string $url, ?Store $store = null): ImportResult
    {
        $shareMeta = $this->shareMeta($url);
        $fallbackCurrency = 'USD';
        $httpStatus = null;
        $httpError = null;

        // Fast path: some SHEIN responses still expose enough JSON in the raw HTML.
        try {
            $response = $this->fetch($url);
            $httpStatus = $response->status();

            if ($response->successful()) {
                $items = $this->extractProducts($response->body(), $url, $fallbackCurrency);
                if ($items !== []) {
                    $currency = strtoupper((string) ($items[0]['currency'] ?? $fallbackCurrency));

                    return ImportResult::success(
                        $items,
                        $currency,
                        array_merge($shareMeta, [
                            'source' => 'shein_http',
                            'items_count' => count($items),
                            'http_status' => $httpStatus,
                        ])
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
            $httpError = class_basename($e);
        }

        // Real-browser fallback: SHEIN often hydrates shared-cart items only after JS runs.
        $browser = $this->browser->import($url);
        $browserItems = $this->extractBrowserProducts($browser, $url, $fallbackCurrency);

        if ($browserItems !== []) {
            $currency = strtoupper((string) ($browserItems[0]['currency'] ?? $fallbackCurrency));

            return ImportResult::success(
                $browserItems,
                $currency,
                array_merge($shareMeta, [
                    'source' => 'shein_playwright',
                    'items_count' => count($browserItems),
                    'http_status' => $httpStatus,
                    'browser_status' => $browser['status'] ?? 'loaded',
                    'browser_final_url' => $browser['final_url'] ?? null,
                    'browser_payload_count' => count($browser['payloads'] ?? []),
                    'browser_dom_item_count' => (int) ($browser['meta']['dom_item_count'] ?? count($browser['items'] ?? [])),
                    'browser_network_item_count' => (int) ($browser['meta']['network_item_count'] ?? 0),
                    'browser_state_item_count' => (int) ($browser['meta']['state_item_count'] ?? 0),
                    'browser_final_item_count' => (int) ($browser['meta']['final_item_count'] ?? count($browserItems)),
                ])
            );
        }

        $browserStatus = (string) ($browser['status'] ?? 'failed');
        $baseMeta = array_merge($shareMeta, [
            'source' => 'shein_playwright',
            'http_status' => $httpStatus,
            'http_error' => $httpError,
            'browser_status' => $browserStatus,
            'browser_message' => $browser['message'] ?? null,
            'browser_payload_count' => count($browser['payloads'] ?? []),
            'browser_dom_item_count' => (int) ($browser['meta']['dom_item_count'] ?? count($browser['items'] ?? [])),
            'browser_network_item_count' => (int) ($browser['meta']['network_item_count'] ?? 0),
            'browser_state_item_count' => (int) ($browser['meta']['state_item_count'] ?? 0),
            'browser_final_item_count' => (int) ($browser['meta']['final_item_count'] ?? 0),
        ]);

        if ($browserStatus === 'challenge') {
            return ImportResult::needsReview(
                'SHEIN طلب تحقق أمني داخل المتصفح. على Mac عطّل Headless من ملف .env، افتح السلة مرة أخرى وأكمل التحقق في نافذة Chromium، وبعدها سيحاول النظام جلب المنتجات تلقائيًا.',
                currency: $fallbackCurrency,
                meta: $baseMeta
            );
        }

        if (in_array($browserStatus, ['unavailable', 'disabled'], true)) {
            return ImportResult::needsReview(
                'تعذر تشغيل متصفح Chromium الخاص باستيراد SHEIN. نفّذ npm install ثم npx playwright install chromium داخل المشروع، وبعدها أعد جلب السلة.',
                currency: $fallbackCurrency,
                meta: $baseMeta
            );
        }

        if ($httpStatus === 429) {
            return ImportResult::needsReview(
                'SHEIN منع الطلب المباشر، وتمت محاولة فتح السلة بمتصفح Chromium أيضًا لكن المنتجات لم تُقرأ. جرّب وضع المتصفح المرئي في Mac إذا ظهر تحقق أمني.',
                currency: $fallbackCurrency,
                meta: $baseMeta
            );
        }

        return ImportResult::needsReview(
            'تم فتح رابط SHEIN وتشغيل JavaScript، لكن لم يتم العثور على عناصر سلة قابلة للقراءة. يمكنك إعادة المحاولة أو فتح المتصفح المرئي إذا كان SHEIN يعرض تحققًا أمنيًا.',
            currency: $fallbackCurrency,
            meta: $baseMeta
        );
    }

    private function extractBrowserProducts(array $browser, string $sourceUrl, string $fallbackCurrency): array
    {
        $items = [];

        foreach ($browser['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'external_id' => (string) ($item['external_id'] ?? ''),
                'name' => trim((string) ($item['name'] ?? '')),
                'product_url' => $this->normalizeUrl((string) ($item['product_url'] ?? ''), $sourceUrl),
                'image_url' => $this->normalizeUrl((string) ($item['image_url'] ?? ''), $sourceUrl),
                'variant' => trim((string) ($item['variant'] ?? '')),
                'color' => trim((string) ($item['color'] ?? '')),
                'size' => trim((string) ($item['size'] ?? '')),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'unit_price_original' => $this->priceAmount($item['unit_price_original'] ?? 0),
                'currency' => strtoupper((string) ($item['currency'] ?? $fallbackCurrency)),
            ];
        }

        // The Playwright worker already combines Network + hydrated state + DOM and
        // applies shared-cart-only filtering. Re-walking the full payload/HTML here
        // can re-introduce SHEIN recommendations as fake cart rows, so its item list
        // is authoritative.
        return $this->deduplicate(array_values(array_filter($items, static fn (array $item) =>
            ($item['name'] ?? '') !== '' || ($item['product_url'] ?? '') !== '' || ($item['image_url'] ?? '') !== ''
        )));
    }

    private function fetch(string $url): Response
    {
        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ar-AE,ar;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Referer' => 'https://m.shein.com/',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Upgrade-Insecure-Requests' => '1',
        ])->timeout(config('services.cart_import.timeout', 15))->get($url);
    }

    private function extractProducts(string $html, string $sourceUrl, string $fallbackCurrency): array
    {
        $items = [];

        // Shared-cart state is preferred because it contains the real quantity/variant.
        foreach ($this->extractEmbeddedJsonPayloads($html) as $payload) {
            $this->walkSheinPayload($payload, $items, [], $sourceUrl, $fallbackCurrency);
        }

        // Product JSON-LD is only a fallback for older/simple SHEIN links.
        if ($items === []) {
            $this->extractJsonLd($html, $items, $sourceUrl, $fallbackCurrency);
        }

        return $this->deduplicate($items);
    }

    private function extractJsonLd(string $html, array &$items, string $sourceUrl, string $fallbackCurrency): void
    {
        if (! preg_match_all('#<script[^>]*type=["\\\']application/ld\+json["\\\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return;
        }

        foreach ($matches[1] as $raw) {
            $decoded = json_decode(html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($decoded)) {
                continue;
            }
            $this->walkJsonLd($decoded, $items, $sourceUrl, $fallbackCurrency);
        }
    }

    private function walkJsonLd(array $node, array &$items, string $sourceUrl, string $fallbackCurrency): void
    {
        if (($node['@type'] ?? null) === 'Product') {
            $offers = $node['offers'] ?? [];
            if (is_array($offers) && isset($offers[0]) && is_array($offers[0])) {
                $offers = $offers[0];
            }
            $price = $this->priceAmount($offers['price'] ?? $offers['lowPrice'] ?? 0);
            $items[] = [
                'external_id' => (string) ($node['sku'] ?? ''),
                'name' => (string) ($node['name'] ?? ''),
                'product_url' => $this->normalizeUrl((string) ($node['url'] ?? ''), $sourceUrl),
                'image_url' => $this->normalizeUrl(is_array($node['image'] ?? null) ? (string) (($node['image'][0] ?? '')) : (string) ($node['image'] ?? ''), $sourceUrl),
                'variant' => '',
                'color' => '',
                'size' => '',
                'quantity' => 1,
                'unit_price_original' => $price,
                'currency' => strtoupper((string) ($offers['priceCurrency'] ?? $fallbackCurrency)),
            ];
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $child) {
                        if (is_array($child)) {
                            $this->walkJsonLd($child, $items, $sourceUrl, $fallbackCurrency);
                        }
                    }
                } else {
                    $this->walkJsonLd($value, $items, $sourceUrl, $fallbackCurrency);
                }
            }
        }
    }

    /**
     * SHEIN commonly embeds product/cart state in JSON script tags or JS assignments
     * (for example __INITIAL_STATE__, productIntroData, gbProductDetail and cart data).
     */
    private function extractEmbeddedJsonPayloads(string $html): array
    {
        $payloads = [];

        if (! preg_match_all('#<script[^>]*>(.*?)</script>#is', $html, $matches)) {
            return $payloads;
        }

        foreach ($matches[1] as $rawScript) {
            $text = html_entity_decode(trim($rawScript), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($text === '') {
                continue;
            }

            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $payloads[] = $decoded;
                $this->collectNestedJsonStrings($decoded, $payloads);
            }

            foreach ([
                'window.__INITIAL_STATE__', '__INITIAL_STATE__', '__NEXT_DATA__', 'productIntroData', 'gbProductDetail',
                'gbRawData', 'pageData', 'cartData', 'shareCartData', 'cartShareData', 'cartShareInfo',
                'goodsList', 'goods_list', 'cartList', 'cart_list',
            ] as $marker) {
                foreach ($this->extractAssignedJson($text, $marker) as $json) {
                    $decoded = json_decode($json, true);
                    if (is_array($decoded)) {
                        $payloads[] = $decoded;
                        $this->collectNestedJsonStrings($decoded, $payloads);
                    }
                }
            }

            // Modern app shells can place the real state inside an escaped JS string
            // (for example Next/React flight chunks). Decode only strings that look cart-related.
            foreach ($this->extractEscapedJsonPayloads($text) as $decodedEscaped) {
                $payloads[] = $decodedEscaped;
                $this->collectNestedJsonStrings($decodedEscaped, $payloads);
            }
        }

        return $payloads;
    }

    private function extractEscapedJsonPayloads(string $script): array
    {
        $payloads = [];
        if (! preg_match_all('~"((?:\\\\.|[^"\\\\]){40,})"~s', $script, $matches)) {
            return $payloads;
        }

        foreach ($matches[0] as $quoted) {
            $decodedString = json_decode($quoted, true);
            if (! is_string($decodedString)) {
                continue;
            }

            $haystack = strtolower($decodedString);
            if (! str_contains($haystack, 'goods_list')
                && ! str_contains($haystack, 'goodslist')
                && ! str_contains($haystack, 'cartshare')
                && ! str_contains($haystack, 'cart_share')
                && ! str_contains($haystack, 'goods_id')) {
                continue;
            }

            $direct = json_decode(trim($decodedString), true);
            if (is_array($direct)) {
                $payloads[] = $direct;
                continue;
            }

            // Sometimes the escaped string is `prefix + JSON + suffix`. Extract balanced objects/arrays.
            $length = strlen($decodedString);
            for ($i = 0; $i < $length; $i++) {
                if (! in_array($decodedString[$i], ['{', '['], true)) {
                    continue;
                }
                $json = $this->balancedJson($decodedString, $i);
                if ($json === null) {
                    continue;
                }
                $value = json_decode($json, true);
                if (is_array($value)) {
                    $encoded = strtolower($json);
                    if (str_contains($encoded, 'goods_id') || str_contains($encoded, 'goods_list') || str_contains($encoded, 'cartshare')) {
                        $payloads[] = $value;
                    }
                    $i += strlen($json) - 1;
                }
            }
        }

        return $payloads;
    }

    private function collectNestedJsonStrings(array $node, array &$payloads, int $depth = 0): void
    {
        if ($depth > 6) {
            return;
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectNestedJsonStrings($value, $payloads, $depth + 1);
                continue;
            }
            if (! is_string($value) || strlen($value) < 20) {
                continue;
            }

            $trimmed = trim($value);
            if (! in_array($trimmed[0] ?? '', ['{', '['], true)) {
                continue;
            }
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $payloads[] = $decoded;
                $this->collectNestedJsonStrings($decoded, $payloads, $depth + 1);
            }
        }
    }

    private function extractAssignedJson(string $script, string $marker): array
    {
        $results = [];
        $offset = 0;

        while (($pos = strpos($script, $marker, $offset)) !== false) {
            $start = $pos + strlen($marker);
            $length = strlen($script);

            while ($start < $length && preg_match('/[\s=:]/', $script[$start])) {
                $start++;
            }

            while ($start < $length && ! in_array($script[$start], ['{', '['], true)) {
                $start++;
            }

            if ($start >= $length) {
                break;
            }

            $json = $this->balancedJson($script, $start);
            if ($json !== null) {
                $results[] = $json;
                $offset = $start + strlen($json);
            } else {
                $offset = $start + 1;
            }
        }

        return $results;
    }

    private function balancedJson(string $text, int $start): ?string
    {
        $open = $text[$start] ?? '';
        $close = $open === '{' ? '}' : ($open === '[' ? ']' : null);
        if ($close === null) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }
            if ($char === $open) {
                $depth++;
            } elseif ($char === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private function walkSheinPayload(array $node, array &$items, array $path, string $sourceUrl, string $fallbackCurrency): void
    {
        $candidate = $this->toCartItem($node, $path, $sourceUrl, $fallbackCurrency);
        if ($candidate !== null) {
            $items[] = $candidate;
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $nextPath = array_merge($path, [(string) $key]);
            if (array_is_list($value)) {
                foreach ($value as $child) {
                    if (is_array($child)) {
                        $this->walkSheinPayload($child, $items, $nextPath, $sourceUrl, $fallbackCurrency);
                    }
                }
            } else {
                $this->walkSheinPayload($value, $items, $nextPath, $sourceUrl, $fallbackCurrency);
            }
        }
    }

    private function toCartItem(array $node, array $path, string $sourceUrl, string $fallbackCurrency): ?array
    {
        $view = $this->mergeKnownProductContainers($node);

        $name = $this->firstScalar($view, [
            'goods_name', 'product_name', 'productName', 'goodsName', 'name', 'title', 'goodsTitle', 'productTitle',
        ]);
        $externalId = $this->firstScalar($view, [
            'goods_id', 'product_id', 'productId', 'goods_sn', 'goodsSn', 'sku_code', 'skuCode', 'sku_id', 'skuId', 'sku',
        ]);
        $priceValue = $this->firstValue($view, [
            'salePrice', 'sale_price', 'discountPrice', 'discount_price', 'productPromotionPrice', 'promotionPrice',
            'unit_price', 'unitPrice', 'price', 'retailPrice', 'retail_price', 'priceInfo', 'price_info',
        ]);
        $price = $this->priceAmount($priceValue);

        if ($name === '' || $price <= 0 || $externalId === '') {
            return null;
        }

        $quantityKeys = [
            'quantity', 'qty', 'goods_num', 'goodsNum', 'goods_quantity', 'goodsQuantity', 'cart_quantity', 'cartQuantity',
            'product_num', 'productNum', 'sku_num', 'skuNum', 'count', 'num',
        ];
        $hasQuantity = $this->hasAnyKey($view, $quantityKeys);
        $pathText = strtolower(implode('.', $path));

        // A SHEIN shared-cart landing page contains recommendation/search feeds too.
        // Exclude those explicitly and only accept rows that either expose a real
        // cart quantity or live under a strong cart/share container.
        if (preg_match('/recommend|suggest|similar|related|guess|youmaylike|you_may_like|hot|feed|search|history|recent|viewed|wishlist|favorite|favourite|trend|flash|campaign|marketing/i', $pathText)) {
            return null;
        }

        $cartContext = (bool) preg_match('/cartshare|cart_share|sharecart|share_cart|cartlist|cart_list|cartitems|cart_items|cartgoods|cart_goods|baglist|bag_list|basket|checkout|selectedgoods|selected_goods|selecteditems|selected_items/i', $pathText);
        if (! $hasQuantity && ! $cartContext) {
            return null;
        }

        $quantity = max(1, (int) $this->firstScalar($view, $quantityKeys, '1'));
        $imageValue = $this->firstValue($view, [
            'goods_img', 'goods_image', 'goodsImage', 'product_img', 'productImage', 'image_url', 'imageUrl', 'image',
            'thumbnail', 'thumb', 'main_image', 'mainImage', 'images',
        ]);
        $image = $this->imageUrl($imageValue);

        $productUrl = $this->firstScalar($view, [
            'product_url', 'productUrl', 'goods_url', 'goodsUrl', 'url', 'detail_url', 'detailUrl', 'goodsLink', 'productLink',
        ]);

        $attributes = $this->extractVariantAttributes($view);
        $color = $this->firstScalar($view, ['color', 'color_name', 'colorName', 'goods_color', 'goodsColor']) ?: ($attributes['color'] ?? '');
        $size = $this->firstScalar($view, ['size', 'size_name', 'sizeName', 'goods_size', 'goodsSize']) ?: ($attributes['size'] ?? '');
        $variant = $this->firstScalar($view, [
            'variant', 'sku_name', 'skuName', 'attr_value', 'attrValue', 'goods_attr', 'goodsAttr', 'skc_name', 'skcName',
            'sku_code', 'skuCode', 'sku_id', 'skuId',
        ]) ?: ($attributes['variant'] ?? '');

        $currency = $this->currencyFromPrice($priceValue)
            ?: $this->firstScalar($view, ['currency', 'currency_code', 'currencyCode'], $fallbackCurrency);

        return [
            'external_id' => $externalId,
            'name' => $name,
            'product_url' => $this->normalizeUrl($productUrl, $sourceUrl),
            'image_url' => $this->normalizeUrl($image, $sourceUrl),
            'variant' => $variant,
            'color' => $color,
            'size' => $size,
            'quantity' => $quantity,
            'unit_price_original' => $price,
            'currency' => strtoupper($currency ?: $fallbackCurrency),
        ];
    }

    private function mergeKnownProductContainers(array $node): array
    {
        $merged = $node;
        foreach ([
            'goods', 'goods_info', 'goodsInfo', 'product', 'product_info', 'productInfo', 'sku', 'sku_info', 'skuInfo',
            'item', 'item_info', 'itemInfo', 'businessInfo', 'business_info',
        ] as $key) {
            if (! isset($node[$key]) || ! is_array($node[$key]) || array_is_list($node[$key])) {
                continue;
            }
            // Parent values (especially quantity) win over nested metadata.
            $merged = array_merge($node[$key], $merged);
        }
        return $merged;
    }

    private function imageUrl(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (! is_array($value)) {
            return '';
        }

        if (array_is_list($value)) {
            foreach ($value as $entry) {
                $url = $this->imageUrl($entry);
                if ($url !== '') {
                    return $url;
                }
            }
            return '';
        }

        foreach (['url', 'src', 'origin_image', 'originImage', 'thumbnail', 'thumb', 'image_url', 'imageUrl', 'goods_img'] as $key) {
            if (array_key_exists($key, $value)) {
                $url = $this->imageUrl($value[$key]);
                if ($url !== '') {
                    return $url;
                }
            }
        }
        return '';
    }

    private function extractVariantAttributes(array $node): array
    {
        $result = ['color' => '', 'size' => '', 'variant' => ''];
        foreach (['sku_sale_attr', 'skuSaleAttr', 'attributes', 'attrs', 'goods_attr_list', 'goodsAttrList', 'attr_list', 'attrList'] as $key) {
            if (! isset($node[$key]) || ! is_array($node[$key])) {
                continue;
            }
            $this->walkAttributeList($node[$key], $result);
        }
        return $result;
    }

    private function walkAttributeList(array $node, array &$result): void
    {
        if (! array_is_list($node)) {
            $label = strtolower($this->firstScalar($node, ['attr_name', 'attrName', 'name', 'label', 'title', 'type']));
            $value = $this->firstScalar($node, ['attr_value', 'attrValue', 'value', 'name_value', 'nameValue', 'label_value', 'labelValue']);
            if ($value !== '') {
                if ($result['color'] === '' && (str_contains($label, 'color') || str_contains($label, 'colour') || str_contains($label, 'لون'))) {
                    $result['color'] = $value;
                } elseif ($result['size'] === '' && (str_contains($label, 'size') || str_contains($label, 'مقاس'))) {
                    $result['size'] = $value;
                } elseif ($result['variant'] === '') {
                    $result['variant'] = $value;
                }
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->walkAttributeList($value, $result);
            }
        }
    }

    private function firstScalar(array $node, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $node)) {
                continue;
            }
            $value = $node[$key];
            if (is_scalar($value)) {
                return trim((string) $value);
            }
        }
        return $default;
    }

    private function firstValue(array $node, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $node)) {
                return $node[$key];
            }
        }
        return null;
    }

    private function hasAnyKey(array $node, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $node)) {
                return true;
            }
        }
        return false;
    }

    private function priceAmount(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        if (is_string($value)) {
            $clean = preg_replace('/[^0-9.,-]/', '', $value) ?: '';
            if (substr_count($clean, ',') === 1 && substr_count($clean, '.') === 0) {
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
            return is_numeric($clean) ? round((float) $clean, 2) : 0.0;
        }

        if (is_array($value)) {
            foreach (['usdAmount', 'usd_amount', 'amount', 'saleAmount', 'price', 'value'] as $key) {
                if (array_key_exists($key, $value)) {
                    $amount = $this->priceAmount($value[$key]);
                    if ($amount > 0) {
                        return $amount;
                    }
                }
            }
        }

        return 0.0;
    }

    private function currencyFromPrice(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        foreach (['usdAmount', 'usd_amount'] as $key) {
            if (array_key_exists($key, $value) && $this->priceAmount($value[$key]) > 0) {
                return 'USD';
            }
        }

        foreach (['currency', 'currencyCode', 'currency_code'] as $key) {
            if (! empty($value[$key]) && is_scalar($value[$key])) {
                return strtoupper((string) $value[$key]);
            }
        }

        $withSymbol = (string) ($value['amountWithSymbol'] ?? '');
        if (preg_match('/\b(AED|USD|EUR|SAR|TRY|GBP|KWD|QAR|BHD|OMR|CAD|AUD|JPY|BRL|ZAR)\b/i', $withSymbol, $match)) {
            return strtoupper($match[1]);
        }

        return null;
    }

    private function normalizeUrl(string $url, string $sourceUrl): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $host = parse_url($sourceUrl, PHP_URL_HOST) ?: 'm.shein.com';
        if (str_starts_with($url, '/')) {
            return 'https://'.$host.$url;
        }
        return 'https://'.$host.'/'.ltrim($url, '/');
    }

    private function deduplicate(array $items): array
    {
        $unique = [];
        foreach ($items as $item) {
            if (empty($item['name'])) {
                continue;
            }
            $key = implode('|', [
                (string) ($item['external_id'] ?? ''),
                (string) ($item['variant'] ?? ''),
                (string) ($item['color'] ?? ''),
                (string) ($item['size'] ?? ''),
                (string) ($item['unit_price_original'] ?? ''),
            ]);
            if (! isset($unique[$key])) {
                $unique[$key] = $item;
                continue;
            }
            // Prefer a cart record carrying the real quantity over a duplicate JSON-LD record.
            if (($item['quantity'] ?? 1) > ($unique[$key]['quantity'] ?? 1)) {
                $unique[$key] = $item;
            }
        }
        return array_values($unique);
    }

    private function shareMeta(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        return array_filter([
            'group_id' => isset($query['group_id']) ? (string) $query['group_id'] : null,
            'shc' => isset($query['shc']) ? (string) $query['shc'] : null,
            'local_country' => isset($query['local_country']) ? strtoupper((string) $query['local_country']) : null,
            'cart_share' => isset($query['cart_share']) ? (string) $query['cart_share'] : null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function currencyFromUrl(string $url): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $country = strtoupper((string) ($query['local_country'] ?? ''));
        return self::COUNTRY_CURRENCY[$country] ?? null;
    }
}
