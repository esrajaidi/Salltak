<?php

namespace App\Services\CartImport\Browser;

use Illuminate\Support\Facades\Process;

class SheinBrowserImporter
{
    /**
     * Render a SHEIN shared-cart URL in a real Chromium browser and return
     * JSON/XHR payloads plus DOM-normalized items for the PHP adapter.
     */
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
        if (! is_file($script)) {
            return [
                'ok' => false,
                'status' => 'unavailable',
                'message' => 'Playwright worker script is missing.',
                'items' => [],
                'payloads' => [],
            ];
        }

        $input = json_encode([
            'url' => $url,
            'timeoutMs' => max(5_000, (int) config('services.cart_import.shein_browser.timeout_ms', 35_000)),
            'headless' => (bool) config('services.cart_import.shein_browser.headless', true),
            'profileDir' => (string) config('services.cart_import.shein_browser.profile_dir', storage_path('app/shein-browser-profile')),
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

        return $decoded;
    }
}
