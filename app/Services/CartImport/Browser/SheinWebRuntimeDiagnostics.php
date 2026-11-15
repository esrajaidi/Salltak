<?php

namespace App\Services\CartImport\Browser;

final class SheinWebRuntimeDiagnostics
{
    /**
     * Sanitize Node/Chromium abort stderr. Never output raw stack traces,
     * URLs, cookie values or account data in Laravel logs or user messages.
     */
    public static function errorCategory(string $stderr): string
    {
        if (trim($stderr) === '') {
            return 'no_stderr';
        }

        if (preg_match('/ERR_MODULE_NOT_FOUND|Cannot find package|Cannot find module/i', $stderr)) {
            return 'module_unavailable';
        }

        if (preg_match('/Out of memory|heap out of memory|Allocation failed/i', $stderr)) {
            return 'memory';
        }

        if (preg_match('/uv_thread_create|thread create|Resource temporarily unavailable/i', $stderr)) {
            return 'thread_resource';
        }

        if (preg_match('/Access is denied|EPERM|EACCES|permission denied/i', $stderr)) {
            return 'permissions';
        }

        if (preg_match('/Assertion failed|Check failed|FATAL ERROR|fatal error in|abort\(\)/i', $stderr)) {
            return 'native_abort';
        }

        return 'unclassified';
    }

    public static function inputSizeBand(int $bytes): string
    {
        return match (true) {
            $bytes < 8192 => 'small',
            $bytes < 131072 => 'medium',
            default => 'large',
        };
    }

    public static function sapi(): string
    {
        return match (PHP_SAPI) {
            'cli' => 'cli',
            'cli-server' => 'php_builtin_server',
            default => 'web_server',
        };
    }
}
