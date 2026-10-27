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

        $normalizedShortUrl = $current;

        for ($attempt = 0; $attempt < self::MAX_REDIRECTS; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-AE,en;q=0.9',
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
                // onelink often responds 200 with an HTML/JS app deeplink,
                // not an HTTP 3xx redirect. The link's group_id can be
                // embedded in shareInfo even when a desktop browser only
                // sees SHEIN's homepage.
                $fromPage = $response->successful()
                    ? $this->shareLandingFromHtml(
                        (string) $response->body(),
                        $normalizedShortUrl,
                    )
                    : null;
                return $fromPage ?? $this->shareLandingOnWww($current, $normalizedShortUrl);
            }

            $next = $this->absoluteUrl($location, $current);
            if ($next === null || ! $this->isAllowedSheinHttpsUrl($next)) {
                return $url;
            }

            $current = $next;

            if (! $this->isShortSheinLink($current)) {
                return $this->shareLandingOnWww($current, $normalizedShortUrl);
            }
        }

        return $this->shareLandingOnWww($current, $url);
    }

    /**
     * Resolve the cart identity only from HTML returned by the trusted
     * onelink chain. Never fetch arbitrary URLs embedded in the document.
     *
     * A valid shortlink supplies shc but not group_id; SHEIN can deliver an
     * HTML/JS app jump page containing the real numeric group ID without an
     * HTTP Location header. A 200 response alone does not mean the cart
     * contents have been retrieved.
     */
    private function shareLandingFromHtml(string $html, string $original): ?string
    {
        if ($html === '' || strlen($html) > 2_000_000
            || ! preg_match('/(?:shareInfo|sharejump|cart\\/share|group_id|groupId)/i', $html)) {
            return null;
        }

        parse_str((string) (parse_url($original, PHP_URL_QUERY) ?: ''), $shortQuery);
        $shc = $shortQuery['shc'] ?? null;
        if (! is_string($shc) || ! preg_match('/^2_[A-Za-z0-9_-]{4,120}$/D', $shc)) {
            return null;
        }

        // Common JSON, JS and URL-encoded forms of group_id seen in SHEIN
        // shareInfo/deeplink pages. Use only a 6–20 digit ID, never infer one.
        $patterns = [
            '~(?:group_id|groupId)[\x22\x27]?\s*:\s*[\x22\x27]?(\d{6,20})(?!\d)~i',
            '/(?:group_id|groupId)(?:=|%3[dD])(\\d{6,20})(?!\\d)/i',
        ];
        $groupId = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $groupId = $matches[1];
                break;
            }
        }
        if ($groupId === null) {
            return null;
        }

        $query = http_build_query([
            'group_id' => $groupId,
            'shc' => $shc,
            'local_country' => 'AE',
            'cart_share' => '1',
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://www.shein.com/ar/cart/share/landing?'.$query;
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

        // Preserve the original onelink share identity. SHEIN may redirect to
        // a different storefront, but that must not replace the pasted token.
        // Never invent a group ID; keep the group provided by the redirect.
        parse_str((string) ($parts['query'] ?? ''), $query);
        parse_str((string) (parse_url($original, PHP_URL_QUERY) ?: ''), $shortQuery);
        foreach (['shc', 'url_from'] as $key) {
            $value = $shortQuery[$key] ?? null;
            if (is_string($value)
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
