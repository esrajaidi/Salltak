<?php

namespace App\Services\CartImport\Browser;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class SheinBrowserImporter
{
    public function import(string $url): array
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

        $script = base_path('scripts/shein-browser-import.mjs');
        $sharedPageScript = base_path('scripts/shein-shared-page-import.mjs');

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

        // New SHEIN onelink shares can open an "Items shared by ..." landing page
        // instead of the classic cart/share endpoint. Read those visible product rows
        // first so a real shared list is not incorrectly reported as an empty cart.
        if (is_file($sharedPageScript)) {
            $sharedProfile = storage_path('app/shein-shared-page-profile/'.Str::uuid());
            try {
                $shared = $this->runWorker($url, $sharedProfile, $sharedPageScript);
            } finally {
                File::deleteDirectory($sharedProfile);
            }

            if (($shared['items'] ?? []) !== []) {
                $shared = $this->withAttemptMeta($shared, 1, false);
                $shared['meta']['source'] = 'shein_shared_items_page';
                return $shared;
            }
        }

        $first = $this->runWorker($url, $primaryProfile, $script);
        $first = $this->withAttemptMeta($first, 1, false);

        if (! $this->shouldRetryEmptyLoadedResult($first)) {
            return $first;
        }

        $retryProfile = storage_path('app/shein-browser-retry/'.Str::uuid());

        try {
            $retry = $this->runWorker($url, $retryProfile, $script);
            $retry = $this->withAttemptMeta($retry, 2, true);
        } finally {
            File::deleteDirectory($retryProfile);
        }

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

    private function runWorker(string $url, string $profileDir, string $script): array
    {
        $input = json_encode([
            'url' => $url,
            'timeoutMs' => max(5_000, (int) config('services.cart_import.shein_browser.timeout_ms', 35_000)),
            'headless' => (bool) config('services.cart_import.shein_browser.headless', true),
            'profileDir' => $profileDir,
            'manualChallengeWaitMs' => max(0, (int) config('services.cart_import.shein_browser.manual_challenge_wait_ms', 60_000)),
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

    private function withAttemptMeta(array $result, int $attemptCount, bool $freshProfileRetry): array
    {
        $result['meta'] = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $result['meta']['import_attempt_count'] = $attemptCount;
        $result['meta']['fresh_profile_retry'] = $freshProfileRetry;

        return $result;
    }
}
