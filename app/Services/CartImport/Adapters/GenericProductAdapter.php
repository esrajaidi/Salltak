<?php

namespace App\Services\CartImport\Adapters;

use App\Models\Store;
use App\Services\CartImport\Contracts\CartSourceAdapter;
use App\Services\CartImport\ImportResult;
use Illuminate\Support\Facades\Http;

class GenericProductAdapter implements CartSourceAdapter
{
    public function canHandle(string $url, ?Store $store = null): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function import(string $url, ?Store $store = null): ImportResult
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; CartlyLibya/1.0)'])
                ->timeout(config('services.cart_import.timeout', 12))->get($url);

            if (! $response->successful()) {
                return ImportResult::failed('تعذر فتح الرابط من الموقع المصدر.');
            }

            $product = $this->extractProduct($response->body(), $url, $store?->currency ?: 'USD');
            if (! $product) {
                return ImportResult::needsReview('تم قبول الرابط، لكن بيانات المنتج غير متاحة تلقائيًا. أكمل البيانات يدويًا.');
            }

            return ImportResult::success([$product], $product['currency'], ['source' => 'generic_product']);
        } catch (\Throwable $e) {
            report($e);
            return ImportResult::needsReview('تعذر تحليل الرابط تلقائيًا. أكمل بيانات المنتج يدويًا.');
        }
    }

    private function extractProduct(string $html, string $url, string $fallbackCurrency): ?array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        if (! @$dom->loadHTML($html)) return null;
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $node) {
            $data = json_decode(trim($node->textContent), true);
            $product = $this->findProduct($data);
            if ($product) {
                $offers = $product['offers'] ?? [];
                if (isset($offers[0])) $offers = $offers[0];
                return [
                    'external_id' => (string) ($product['sku'] ?? ''),
                    'name' => (string) ($product['name'] ?? 'منتج'),
                    'product_url' => (string) ($product['url'] ?? $url),
                    'image_url' => is_array($product['image'] ?? null) ? (string) ($product['image'][0] ?? '') : (string) ($product['image'] ?? ''),
                    'quantity' => 1,
                    'unit_price_original' => (float) ($offers['price'] ?? $offers['lowPrice'] ?? 0),
                    'currency' => strtoupper((string) ($offers['priceCurrency'] ?? $fallbackCurrency)),
                ];
            }
        }

        $title = $this->meta($xpath, 'property', 'og:title') ?: $this->meta($xpath, 'name', 'twitter:title');
        $image = $this->meta($xpath, 'property', 'og:image') ?: '';
        if (! $title) return null;

        return ['external_id' => '', 'name' => $title, 'product_url' => $url, 'image_url' => $image, 'quantity' => 1, 'unit_price_original' => 0, 'currency' => strtoupper($fallbackCurrency)];
    }

    private function findProduct(mixed $node): ?array
    {
        if (! is_array($node)) return null;
        if (($node['@type'] ?? null) === 'Product') return $node;
        foreach ($node as $value) {
            if (is_array($value) && ($found = $this->findProduct($value))) return $found;
        }
        return null;
    }

    private function meta(\DOMXPath $xpath, string $attribute, string $value): ?string
    {
        $nodes = $xpath->query("//meta[@{$attribute}='{$value}']/@content");
        return $nodes && $nodes->length ? trim($nodes->item(0)->nodeValue) : null;
    }
}
