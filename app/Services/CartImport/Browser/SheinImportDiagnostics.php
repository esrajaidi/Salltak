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
        $allowedStages = ['shared_www', 'shared_mobile', 'shared_mobile_fallback', 'shared_guest_fallback', 'shared_desktop', 'shared_enrichment', 'legacy_primary', 'legacy_retry'];
        $allowedStatuses = ['loaded', 'missing_usd_prices', 'shared_page_unreadable', 'ae_price_unverified', 'mobile_share_price_needs_review', 'guest_price_needs_review', 'account_price_unverified', 'account_session_required', 'www_price_source_unavailable', 'not_shared_page', 'challenge', 'failed', 'unavailable', 'disabled', 'invalid_url', 'timeout', 'empty'];
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
            'ae_price_unverified' => 'ae_currency_not_confirmed',
            'account_session_required' => 'purchaser_login_missing',
            'www_price_source_unavailable' => 'www_price_evidence_missing',
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
            // Allowlisted share/BFF evidence only. No group ID, token, URL or
            // remote response body is ever included in Laravel logs.
            'share_group_id_present' => ($meta['share_group_id_present'] ?? false) === true,
            'share_token_present' => ($meta['share_token_present'] ?? false) === true,
            'direct_bff_attempted' => ($meta['direct_bff_attempted'] ?? false) === true,
            'direct_bff_error_category' => in_array(($meta['direct_bff_error_category'] ?? ''), [
                'none', 'timeout', 'dns', 'tls', 'connection_reset', 'connection_refused', 'context_closed', 'browser_network',
            ], true) ? $meta['direct_bff_error_category'] : 'unknown',
            'worker_stage' => in_array(($meta['worker_stage'] ?? ''), [
                'launch', 'session', 'resolve', 'navigation', 'responses', 'cart_request', 'extract',
            ], true) ? $meta['worker_stage'] : 'unknown',
            'direct_bff_http_status' => $count($meta['direct_bff_http_status'] ?? 0),
            'direct_bff_outcome' => in_array(($meta['direct_bff_outcome'] ?? ''), [
                'not_attempted', 'missing_group_id', 'bound_share_response',
                'unbound_share_response', 'oversized_response',
                'non_json_response', 'http_error', 'request_failed',
            ], true) ? $meta['direct_bff_outcome'] : 'other',
            'share_response_seen' => ($meta['share_response_seen'] ?? false) === true,
            'share_bound_candidate_count' => $count($meta['share_bound_candidate_count'] ?? 0),
            'bound_bff_response_count' => $count($meta['bound_bff_response_count'] ?? 0),
            'bound_bff_candidate_count' => $count($meta['bound_bff_candidate_count'] ?? 0),
            'bound_bff_valid_item_count' => $count($meta['bound_bff_valid_item_count'] ?? 0),
            'bound_bff_missing_usd_count' => $count($meta['bound_bff_missing_usd_count'] ?? 0),
            'pricing_country_verified' => ($meta['pricing_country_verified'] ?? false) === true,
            'www_source_rejected' => ($meta['rejected_non_www_prices'] ?? false) === true,
            'www_source_evidence' => ($meta['price_source_verified_host'] ?? '') === 'www.shein.com',
            'share_response_count' => $count($responseClasses['share'] ?? 0),
            'other_response_count' => $count($responseClasses['other'] ?? 0),
            'duration_ms' => max(0, min(600_000, $durationMs)),
            'runtime_abort_category' => in_array(($meta['runtime_abort_category'] ?? ''), [
                'no_stderr', 'module_unavailable', 'memory', 'thread_resource',
                'permissions', 'native_abort', 'unclassified',
            ], true) ? $meta['runtime_abort_category'] : 'not_applicable',
            'runtime_web_smoke' => in_array(($meta['runtime_web_smoke'] ?? ''), [
                'passed', 'failed', 'not_checked',
            ], true) ? $meta['runtime_web_smoke'] : 'not_applicable',
            'runtime_input_size_band' => in_array(($meta['runtime_input_size_band'] ?? ''), [
                'small', 'medium', 'large',
            ], true) ? $meta['runtime_input_size_band'] : 'not_applicable',
            'runtime_owner_session_present' => ($meta['runtime_owner_session_present'] ?? false) === true,
            'runtime_sapi' => in_array(($meta['runtime_sapi'] ?? ''), [
                'cli', 'php_builtin_server', 'web_server',
            ], true) ? $meta['runtime_sapi'] : 'unknown',
            'exit_code' => is_int($exitCode) && $exitCode >= 0 && $exitCode <= 255 ? $exitCode : null,
        ];
    }
}
