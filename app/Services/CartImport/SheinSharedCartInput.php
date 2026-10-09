<?php

namespace App\Services\CartImport;

use InvalidArgumentException;

final class SheinSharedCartInput
{
    /**
     * Accept a bare link or the complete text pasted from SHEIN Share Cart.
     * Keep URLs unmodified so the Playwright importer can resolve onelink safely.
     */
    public static function extract(string $input): string
    {
        $input = html_entity_decode(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! preg_match_all('~https://[^\s\p{Z}<>"\']+~iu', $input, $matches)) {
            throw new InvalidArgumentException('الصقي رابط مشاركة SHEIN، حتى لو كان داخل رسالة المشاركة الكاملة.');
        }

        foreach ($matches[0] as $candidate) {
            // Ignore punctuation that might follow a copied link in a sentence.
            $candidate = preg_replace('/[.,،؛!؟)\]}]+$/u', '', $candidate) ?? $candidate;
            if (self::isSupported($candidate)) {
                return $candidate;
            }
        }

        throw new InvalidArgumentException('نقبل روابط سلة SHEIN من onelink.shein.com أو رابط مشاركة السلة الذي يحتوي group_id.');
    }

    public static function isSupported(string $candidate): bool
    {
        if (strlen($candidate) > 2000 || filter_var($candidate, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($candidate);
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($host === 'onelink.shein.com') {
            return (bool) preg_match('~^/\d{1,4}/[a-zA-Z0-9_-]{5,100}/?$~D', $path);
        }

        if (! in_array($host, ['m.shein.com','ar.shein.com','www.shein.com','shein.com'], true)
            || ! str_contains($path, '/cart/share/landing')) {
            return false;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $groupId = $query['group_id'] ?? null;
        return is_string($groupId) && (bool) preg_match('/^\d{6,20}$/D', $groupId);
    }
}
