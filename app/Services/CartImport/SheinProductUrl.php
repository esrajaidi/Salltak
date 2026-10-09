<?php

namespace App\Services\CartImport;

/**
 * Canonicalize customer-facing SHEIN product links to www.shein.com.
 *
 * Keep share-cart URLs and the mobile BFF endpoint unchanged because the
 * shared-cart importer uses those hosts independently of product-page links.
 */
final class SheinProductUrl
{
    public static function canonicalize(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || ($host !== 'shein.com' && ! str_ends_with($host, '.shein.com'))
            || $host === 'onelink.shein.com') {
            return $url;
        }

        // The mobile share page isn't a product detail page.
        $path = (string) ($parts['path'] ?? '/');
        if (str_contains($path, '/cart/share/') || str_contains($path, '/bff-api/')) {
            return $url;
        }

        return 'https://www.shein.com'.$path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
