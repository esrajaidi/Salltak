<?php

namespace App\Services\CartImport;

final class SheinPreviewGuard
{
    public static function isSheinShare(string $url): bool
    {
        if (!self::isSheinSource($url)) {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        return $host === 'onelink.shein.com'
            || str_contains($path, '/cart/share')
            || str_contains($path, '/share/landing');
    }

    public static function requiresReview(array $snapshot): bool
    {
        return self::isSheinSource((string) ($snapshot['source_url'] ?? '')) && !self::canSave($snapshot);
    }

    public static function canSave(array $snapshot): bool
    {
        if (!self::isSheinSource((string) ($snapshot['source_url'] ?? ''))) {
            return true;
        }
        $items = $snapshot['items'] ?? null;
        if (!self::isSheinShare((string) ($snapshot['source_url'] ?? ''))
            || ($snapshot['import_status'] ?? null) !== 'success'
            || ($snapshot['share_complete'] ?? false) !== true
            || strtoupper((string) ($snapshot['source_currency'] ?? '')) !== 'USD'
            || !is_array($items) || $items === []
            || !is_int($snapshot['expected_count'] ?? null)
            || $snapshot['expected_count'] !== count($items)) {
            return false;
        }
        foreach ($items as $item) {
            if (!is_array($item)
                || ($item['price_status'] ?? null) !== 'confirmed'
                || !in_array($item['price_source'] ?? null, ['network_exact_sku', 'visible_same_card', 'share_state_exact_sku'], true)
                || !is_numeric($item['unit_price_original'] ?? null)
                || (float) $item['unit_price_original'] <= 0
                || !isset($item['quantity']) || !is_numeric($item['quantity'])
                || (int) $item['quantity'] < 1 || (int) $item['quantity'] > 999) {
                return false;
            }
        }
        return true;
    }

    private static function isSheinSource(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        return $scheme === 'https' && ($host === 'shein.com' || str_ends_with($host, '.shein.com'));
    }
}
