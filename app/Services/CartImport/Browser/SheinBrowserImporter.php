<?php

namespace App\Services\CartImport\Browser;

use App\Services\CartImport\SheinSessionVault;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class SheinBrowserImporter
{
    public function import(string $url, ?bool $useOwnerSession = null): array
    {
        if (! config('services.cart_import.shein_browser.enabled', true)) {
            return [
                'ok' => false,
                'status' => 'disabled',
                'message' => 'Browser importer is disabled.',
                'items' => [],
                'payloads' => [],
            ];
        }

        // A null override follows the vault's admin activation flag. True is
        // only passed by the protected admin test; false always means guest.
        $accountState = $useOwnerSession === false ? null
            : app(SheinSessionVault::class)->read($useOwnerSession === true);
        $traceId = (string) Str::uuid();
        $script = base_path('scripts/shein-browser-import.mjs');
        $sharedPageScript = base_path('scripts/shein-shared-page-import.mjs');
        $sharedRetryScript = base_path('scripts/shein-shared-page-retry.mjs');

        if (! is_file($script)) {
            return [
                'ok' => false,
                'status' => 'unavailable',
                'message' => 'Playwright worker script is missing.',
                'items' => [],
                'payloads' => [],
            ];
        }

        $primaryProfile = (string) config('services.cart_import.shein_browser.profile_dir', storage_path('app/shein-browser-profile'));
        $shared = null;

        if (is_file($sharedPageScript)) {
            $sharedProfile = storage_path('app/shein-shared-page-profile/'.Str::uuid());
            try {
                $shared = $this->runWorker($url, $sharedProfile, $sharedPageScript, 'shared_www', $traceId, $accountState, 'www.shein.com');
            } finally {
                File::deleteDirectory($sharedProfile);
            }

            $shared = $this->withAttemptMeta($shared, 1, false);
            $shared['meta']['source'] = 'shein_shared_items_page';
            $shared['meta']['share_fetch_host'] = 'www.shein.com';

            // Use the user's purchasing site for the first attempt. If SHEIN
            // does not offer the shared-cart API on www, preserve the products
            // through an explicitly labeled mobile-service fallback.
            if ($this->isSharedCartUrl($url) && ($shared['items'] ?? []) === []) {
                $mobileProfile = storage_path('app/shein-shared-mobile-fallback/'.Str::uuid());
                try {
                    $mobile = $this->runWorker(
                        $url, $mobileProfile, $sharedPageScript,
                        'shared_mobile_fallback', $traceId, $accountState, 'm.shein.com'
                    );
                } finally {
                    File::deleteDirectory($mobileProfile);
                }

                if (($mobile['items'] ?? []) !== []) {
                    $mobile = $this->withAttemptMeta($mobile, 2, true);
                    $mobile['items'] = SheinImportedItemCleaner::clean($mobile['items']);
                    $mobile['status'] = 'mobile_share_price_needs_review';
                    $mobile['message'] = 'تعذر جلب السلة من www.shein.com. تم استرجاعها من خدمة المشاركة m.shein.com بأسعار تحتاج مراجعة من حساب الشراء.';
                    $mobile['meta']['www_attempt_status'] = (string) ($shared['status'] ?? 'failed');
                    $mobile['meta']['mobile_fallback_used'] = true;
                    $mobile['meta']['share_fetch_host'] = 'm.shein.com';
                    return $mobile;
                }

                $shared['meta']['mobile_fallback_status'] = (string) ($mobile['status'] ?? 'failed');
                $shared['meta']['mobile_fallback_empty'] = true;
            }

            // A logged-in SHEIN browser can display a different page and
            // return no shared items even while the unauthenticated share API
            // still works. Preserve the imported products as an explicitly
            // unverified guest quote, never as a confirmed account price.
            // The owner's encrypted cookies are NOT passed to this retry.
            if ($accountState !== null && $this->isSharedCartUrl($url) && ($shared['items'] ?? []) === []) {
                $guestProfile = storage_path('app/shein-shared-page-guest/'.Str::uuid());
                try {
                    $guest = $this->runWorker($url, $guestProfile, $sharedPageScript, 'shared_guest_fallback', $traceId, null, 'm.shein.com');
                } finally {
                    File::deleteDirectory($guestProfile);
                }

                if (($guest['items'] ?? []) !== []) {
                    $guest = $this->withAttemptMeta($guest, 2, true);
                    $guest['items'] = SheinImportedItemCleaner::clean($guest['items']);
                    $guest['status'] = 'guest_price_needs_review';
                    $guest['message'] = 'تعذر جلب المنتجات بجلسة حساب الشراء. تم استرجاع منتجات السلة كزائر؛ الأسعار تحتاج مراجعة من حساب SHEIN.';
                    $guest['meta']['owner_session_fallback'] = true;
                    $guest['meta']['owner_session_attempt_status'] = (string) ($shared['status'] ?? 'failed');
                    $guest['meta']['account_session_applied'] = false;
                    $guest['meta']['source'] = 'shein_shared_guest_fallback';
                    return $guest;
                }

                // Keep the original diagnostic but do not start an extra
                // 55-second authenticated desktop retry after two empty
                // attempts. A fresh user retry may work when SHEIN recovers.
                $shared['meta']['guest_retry_status'] = (string) ($guest['status'] ?? 'failed');
                $shared['meta']['guest_retry_empty'] = true;
                $shared['meta']['owner_session_fallback'] = true;
                return $shared;
            }

            if (($shared['items'] ?? []) !== []) {
                // A SHEIN redirect can lead www storefront navigation to a
                // mobile API. The true BFF response host is more trustworthy
                // than the URL we originally asked Chromium to open.
                $actualBffHost = (string) ($shared['meta']['bound_bff_response_host'] ?? '');
                if ($actualBffHost === 'm.shein.com') {
                    $shared['status'] = 'mobile_share_price_needs_review';
                    $shared['message'] = 'تمت إعادة توجيه قراءة السلة إلى خدمة SHEIN المتنقلة. الأسعار للمراجعة وليست أسعار حساب الشراء المؤكدة.';
                    $shared['meta']['mobile_fallback_used'] = true;
                    $shared['meta']['share_fetch_host'] = 'm.shein.com';
                }
                // Applying cookies does not prove that SHEIN priced the BFF
                // response for the logged-in purchaser. Manual comparison is
                // required before checkout even for AED storefront evidence.
                if ($accountState !== null && (string) ($shared['status'] ?? '') === 'loaded') {
                    $shared['status'] = 'account_price_unverified';
                    $shared['message'] = 'تم جلب منتجات السلة من www.shein.com، لكن لم يتم إثبات تطابق السعر مع حساب الشراء. راجعي الأسعار.';
                }
                if (SheinImportedItemCleaner::needsEnrichment($shared['items'])) {
                    $fallbackProfile = storage_path('app/shein-browser-enrichment/'.Str::uuid());
                    try {
                        $fallback = $this->runWorker($url, $fallbackProfile, $script, 'shared_enrichment', $traceId);
                    } finally {
                        File::deleteDirectory($fallbackProfile);
                    }
                    $shared['items'] = SheinImportedItemCleaner::clean(
                        $shared['items'],
                        is_array($fallback['items'] ?? null) ? $fallback['items'] : []
                    );
                    $shared['meta']['item_enrichment_attempted'] = true;
                    $shared['meta']['item_enrichment_fallback_count'] = count($fallback['items'] ?? []);
                } else {
                    $shared['items'] = SheinImportedItemCleaner::clean($shared['items']);
                    $shared['meta']['item_enrichment_attempted'] = false;
                }

                return $shared;
            }

            $shouldRetryShared = $this->isSharedCartUrl($url)
                || in_array((string) ($shared['status'] ?? ''), ['missing_usd_prices', 'shared_page_unreadable', 'not_shared_page'], true);

            if ($shouldRetryShared && is_file($sharedRetryScript)) {
                $sharedRetryProfile = storage_path('app/shein-shared-page-retry/'.Str::uuid());
                try {
                    $sharedRetry = $this->runWorker($url, $sharedRetryProfile, $sharedRetryScript, 'shared_desktop', $traceId, $accountState);
                } finally {
                    File::deleteDirectory($sharedRetryProfile);
                }

                $sharedRetry = $this->withAttemptMeta($sharedRetry, 2, true);
                $sharedRetry['meta']['source'] = 'shein_shared_items_page_retry';
                $sharedRetry['meta']['shared_page_retry'] = true;
                $sharedRetry['meta']['initial_shared_status'] = $shared['status'] ?? null;

                if (($sharedRetry['items'] ?? []) !== []) {
                    $sharedRetry['items'] = SheinImportedItemCleaner::clean($sharedRetry['items']);
                    return $sharedRetry;
                }

                if ($this->isSharedCartUrl($url)) {
                    return $sharedRetry;
                }

                if (in_array((string) ($sharedRetry['status'] ?? ''), ['missing_usd_prices', 'shared_page_unreadable'], true)) {
                    return $sharedRetry;
                }
            }

            if (in_array((string) ($shared['status'] ?? ''), ['missing_usd_prices', 'shared_page_unreadable'], true)) {
                return $shared;
            }

            if ($this->isSharedCartUrl($url)) {
                return $shared;
            }
        }

        $first = $this->runWorker($url, $primaryProfile, $script, 'legacy_primary', $traceId);
        $first = $this->withAttemptMeta($first, 1, false);
        $first['items'] = SheinImportedItemCleaner::clean($first['items'] ?? []);

        if (($first['items'] ?? []) !== []) {
            return $first;
        }

        if (! $this->shouldRetryEmptyLoadedResult($first)) {
            return $first;
        }

        $retryProfile = storage_path('app/shein-browser-retry/'.Str::uuid());

        try {
            $retry = $this->runWorker($url, $retryProfile, $script, 'legacy_retry', $traceId);
            $retry = $this->withAttemptMeta($retry, 2, true);
        } finally {
            File::deleteDirectory($retryProfile);
        }
        $retry['items'] = SheinImportedItemCleaner::clean($retry['items'] ?? []);

        if (($retry['items'] ?? []) !== []) {
            return $retry;
        }

        if (($retry['status'] ?? null) === 'challenge') {
            return $retry;
        }

        if (! in_array((string) ($retry['status'] ?? ''), ['loaded'], true)) {
            $first['meta']['import_attempt_count'] = 2;
            $first['meta']['fresh_profile_retry'] = true;
            $first['meta']['retry_status'] = $retry['status'] ?? null;
            return $first;
        }

        return $retry;
    }

    private function runWorker(string $url, string $profileDir, string $script, string $stage, string $traceId, ?array $accountState = null, string $shareHost = 'www.shein.com'): array
    {
        $startedAt = microtime(true);
        $result = $this->runWorkerRaw($url, $profileDir, $script, $accountState, $shareHost);

        Log::channel('stderr')->info('SHEIN import attempt', SheinImportDiagnostics::attempt(
            $result,
            $stage,
            $traceId,
            (int) round((microtime(true) - $startedAt) * 1000)
        ));

        return $result;
    }

    private function runWorkerRaw(string $url, string $profileDir, string $script, ?array $accountState = null, string $shareHost = 'www.shein.com'): array
    {
        $input = json_encode([
            'url' => $url,
            'timeoutMs' => max(5_000, (int) config('services.cart_import.shein_browser.timeout_ms', 35_000)),
            'headless' => (bool) config('services.cart_import.shein_browser.headless', true),
            'profileDir' => $profileDir,
            'manualChallengeWaitMs' => max(0, (int) config('services.cart_import.shein_browser.manual_challenge_wait_ms', 60_000)),
            // Only passed through STDIN to the strict share importer; never logs or URLs.
            'accountSession' => $accountState,
            'shareHost' => $shareHost,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $result = Process::path(base_path())
                ->input($input ?: '{}')
                ->timeout(max(20, (int) config('services.cart_import.shein_browser.process_timeout', 110)))
                ->run([
                    (string) config('services.cart_import.shein_browser.node_binary', 'node'),
                    $script,
                ]);
        } catch (\Throwable $e) {
            report($e);

            return [
                'ok' => false,
                'status' => 'unavailable',
                'message' => 'Could not start the Playwright browser worker.',
                'error' => class_basename($e),
                'items' => [],
                'payloads' => [],
            ];
        }

        $decoded = json_decode(trim($result->output()), true);
        if (! is_array($decoded)) {
            $errorOutput = trim($result->errorOutput());
            $unavailable = str_contains($errorOutput, 'ERR_MODULE_NOT_FOUND')
                || str_contains($errorOutput, 'Cannot find package')
                || str_contains($errorOutput, "Executable doesn't exist")
                || str_contains($errorOutput, 'playwright install');

            return [
                'ok' => false,
                'status' => $unavailable ? 'unavailable' : 'failed',
                'message' => $errorOutput ?: 'Playwright returned an invalid response.',
                'exit_code' => $result->exitCode(),
                'items' => [],
                'payloads' => [],
            ];
        }

        $decoded['exit_code'] = $result->exitCode();
        $decoded['stderr'] = trim($result->errorOutput());
        $decoded['items'] = is_array($decoded['items'] ?? null) ? $decoded['items'] : [];
        $decoded['payloads'] = is_array($decoded['payloads'] ?? null) ? $decoded['payloads'] : [];
        $decoded['meta'] = is_array($decoded['meta'] ?? null) ? $decoded['meta'] : [];

        return $decoded;
    }

    private function shouldRetryEmptyLoadedResult(array $result): bool
    {
        return (string) ($result['status'] ?? '') === 'loaded'
            && ($result['items'] ?? []) === [];
    }

    private function isSharedCartUrl(string $url): bool
    {
        $normalized = strtolower($url);

        return str_contains($normalized, 'onelink.shein.com')
            || str_contains($normalized, '/cart/share')
            || str_contains($normalized, '/share/landing')
            || str_contains($normalized, 'cart_share=1')
            || str_contains($normalized, 'group_id=')
            || str_contains($normalized, 'shc=');
    }

    private function withAttemptMeta(array $result, int $attemptCount, bool $freshProfileRetry): array
    {
        $result['meta'] = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $result['meta']['import_attempt_count'] = $attemptCount;
        $result['meta']['fresh_profile_retry'] = $freshProfileRetry;

        return $result;
    }
}
