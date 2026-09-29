<?php

namespace App\Services\CartImport\Browser;

final class SheinImportDiagnostics
{
    /**
     * Return allowlisted, non-sensitive fields only. Never log a raw SHEIN URL,
     * query string, exception, product, payload, cookie or worker stderr.
     */
    public static function attempt(array $result, string $stage, string $traceId, int $durationMs): array
    {
        $meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $allowedStages = ['shared_mobile', 'shared_desktop', 'shared_enrichment', 'legacy_primary', 'legacy_retry'];
        $allowedStatuses = ['loaded', 'missing_usd_prices', 'shared_page_unreadable', 'not_shared_page', 'challenge', 'failed', 'unavailable', 'disabled', 'invalid_url', 'timeout', 'empty'];
        $stage = in_array($stage, $allowedStages, true) ? $stage : 'other';
        $status = (string) ($result['status'] ?? '');
        $status = in_array($status, $allowedStatuses, true) ? $status : 'other';

        $url = is_string($result['final_url'] ?? null) ? $result['final_url'] : '';
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $isShein = (bool) preg_match('/^(?:[a-z0-9-]+\.)*shein\.com$/D', $host);
        $finalHost = $isShein ? $host : ($host === '' ? 'unavailable' : 'external');
        $path = $isShein ? strtolower((string) parse_url($url, PHP_URL_PATH)) : '';
        $finalPage = match (true) {
            str_contains($path, '/cart/share'), str_contains($path, '/share/landing') => 'share_landing',
            str_contains($path, '/cart') => 'cart',
            str_contains($path, '/product'), str_contains($path, '-p-') => 'product',
            $host === '' => 'unavailable',
            default => 'other',
        };
        $failureReason = match ($status) {
            'loaded' => 'none',
            'missing_usd_prices' => 'usd_price_unconfirmed',
            'shared_page_unreadable' => 'no_visible_products',
            'not_shared_page' => 'not_share_landing',
            'challenge' => 'security_challenge',
            'unavailable' => 'worker_unavailable',
            'disabled' => 'worker_disabled',
            'invalid_url' => 'invalid_url',
            'timeout' => 'worker_timeout',
            'empty' => 'no_items',
            default => 'worker_failed',
        };
        $count = static fn (mixed $value): int => is_numeric($value)
            ? max(0, min(100_000, (int) $value)) : 0;
        $responseClasses = is_array($meta['response_class_counts'] ?? null) ? $meta['response_class_counts'] : [];
        $exitCode = $result['exit_code'] ?? null;

        return [
            'trace_id' => preg_match('/^[0-9a-f-]{36}$/Di', $traceId) ? $traceId : 'unavailable',
            'stage' => $stage,
            'status' => $status,
            'failure_reason' => $failureReason,
            'final_host' => $finalHost,
            'final_page' => $finalPage,
            'items_count' => count(is_array($result['items'] ?? null) ? $result['items'] : []),
            'visible_product_count' => $count($meta['visible_product_count'] ?? $meta['dom_item_count'] ?? 0),
            'candidate_root_count' => $count($meta['candidate_root_count'] ?? 0),
            'image_candidate_count' => $count($meta['image_candidate_count'] ?? 0),
            'missing_usd_price_count' => $count($meta['missing_usd_price_count'] ?? 0),
            'network_usd_price_count' => $count($meta['network_usd_price_count'] ?? 0),
            'inspected_response_count' => $count($meta['inspected_response_count'] ?? 0),
            'share_response_seen' => ($meta['share_response_seen'] ?? false) === true,
            'share_bound_candidate_count' => $count($meta['share_bound_candidate_count'] ?? 0),
            'share_response_count' => $count($responseClasses['share'] ?? 0),
            'other_response_count' => $count($responseClasses['other'] ?? 0),
            'duration_ms' => max(0, min(600_000, $durationMs)),
            'exit_code' => is_int($exitCode) && $exitCode >= 0 && $exitCode <= 255 ? $exitCode : null,
        ];
    }
}
