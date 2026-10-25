<?php

namespace App\Services\CartImport;

use App\Services\StoreUrlClassifier;
use Illuminate\Support\Facades\Http;

class SheinUrlResolver
{
    private const MAX_REDIRECTS = 6;

    public function __construct(
        private readonly StoreUrlClassifier $classifier,
    ) {}

    public function resolve(string $url): string
    {
        if (! $this->isShortSheinLink($url)) {
            return $url;
        }

        // SHEIN share messages sometimes concatenate "/?url_from=" into the
        // shc query value. Repair that separator without changing the token.
        $current = preg_replace(
            '~([?&]shc=[A-Za-z0-9_-]+)/\\?url_from=([A-Za-z0-9_-]+)(?=&|$)~',
            '$1&url_from=$2',
            $url,
        ) ?? $url;

        for ($attempt = 0; $attempt < self::MAX_REDIRECTS; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'ar,en-US;q=0.8,en;q=0.7',
                ])
                    ->withoutRedirecting()
                    ->timeout((int) config('services.cart_import.timeout', 15))
                    ->get($current);
            } catch (\Throwable $e) {
                report($e);
                return $url;
            }

            $location = trim((string) $response->header('Location'));
            if ($location === '') {
                return $this->shareLandingOnWww($current, $url);
            }

            $next = $this->absoluteUrl($location, $current);
            if ($next === null || ! $this->isAllowedSheinHttpsUrl($next)) {
                return $url;
            }

            $current = $next;

            if (! $this->isShortSheinLink($current)) {
                return $current;
            }
        }

        return $this->shareLandingOnWww($current, $url);
    }

    /**
     * Keep the same SHEIN shared cart but prefer the purchasing website when
     * a short onelink resolves to a regional/mobile share landing page.
     * This only rewrites *share pages*, never product links, arbitrary URLs or
     * the short-link itself. SHEIN can still issue its own country redirect.
     */
    private function shareLandingOnWww(string $resolved, string $original): string
    {
        $parts = parse_url($resolved);
        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return $resolved;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '/');
        if ($host === 'onelink.shein.com'
            || ! $this->classifier->isShein($resolved)
            || ! (str_contains($path, '/cart/share/') || str_contains($path, '/share/landing'))) {
            return $resolved;
        }

        // Preserve the onelink share identity if the redirect omitted it.
        // Do not invent a group ID or replace an identity already returned by SHEIN.
        parse_str((string) ($parts['query'] ?? ''), $query);
        parse_str((string) (parse_url($original, PHP_URL_QUERY) ?: ''), $shortQuery);
        foreach (['shc', 'url_from'] as $key) {
            $value = $shortQuery[$key] ?? null;
            if (! isset($query[$key]) && is_string($value)
                && preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $value)) {
                $query[$key] = $value;
            }
        }

        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return 'https://www.shein.com'.$path
            .($queryString === '' ? '' : '?'.$queryString)
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    private function isShortSheinLink(string $url): bool
    {
        return $this->classifier->host($url) === 'onelink.shein.com';
    }

    private function isAllowedSheinHttpsUrl(string $url): bool
    {
        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
            && $this->classifier->isShein($url);
    }

    private function absoluteUrl(string $location, string $baseUrl): ?string
    {
        if (str_starts_with($location, '//')) {
            return 'https:'.$location;
        }

        $scheme = strtolower((string) parse_url($location, PHP_URL_SCHEME));
        if ($scheme !== '') {
            return $scheme === 'https' ? $location : null;
        }

        $baseHost = (string) parse_url($baseUrl, PHP_URL_HOST);
        if ($baseHost === '') {
            return null;
        }

        if (str_starts_with($location, '/')) {
            return 'https://'.$baseHost.$location;
        }

        $basePath = (string) parse_url($baseUrl, PHP_URL_PATH);
        $directory = trim(str_replace('\\', '/', dirname($basePath)), '/.');
        $prefix = $directory === '' ? '' : '/'.$directory;

        return 'https://'.$baseHost.$prefix.'/'.ltrim($location, '/');
    }
}
