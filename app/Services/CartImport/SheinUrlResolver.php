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
                if (! $response->successful()) {
                    return $url;
                }

                $html = (string) $response->body();
                if ($this->isTrustedAppjumpUrl($current)) {
                    return $this->shareLandingFromAppjumpHtml($html, $normalizedShortUrl) ?? $url;
                }

                $fromPage = $this->shareLandingFromHtml($html, $normalizedShortUrl)
                    ?? $this->shareLandingFromDeeplinkHtml($html, $normalizedShortUrl);
                if ($fromPage !== null) {
                    return $fromPage;
                }

                // SHEIN's onelink sometimes returns HTTP 200 with two hidden
                // inputs instead of a Location header: a sheinlink deeplink
                // and a SHEIN-owned appjump URL. Only follow the one exact
                // trusted appjump host/path, never an arbitrary HTML URL.
                $appjump = $this->trustedAppjumpFromHtml($html);
                if ($appjump !== null && $this->isShortSheinLink($current)) {
                    $current = $appjump;
                    continue;
                }

                return $this->shareLandingOnWww($current, $normalizedShortUrl);
            }

            $next = $this->absoluteUrl($location, $current);
            if ($next === null || ! $this->isAllowedSheinHttpsUrl($next)) {
                return $url;
            }

            $current = $next;

            if (! $this->isShortSheinLink($current) && ! $this->isTrustedAppjumpUrl($current)) {
                return $this->shareLandingOnWww($current, $normalizedShortUrl);
            }
        }

        return $this->shareLandingOnWww($current, $url);
    }

    /**
     * Only read static hidden input values from SHEIN's onelink page.
     * Never run JavaScript found in remote HTML or log raw link tokens.
     */
    private function hiddenInputValue(string $html, string $id): ?string
    {
        if (strlen($html) > 2_000_000
            || ! preg_match_all('~<input\b[^>]{0,8192}>~i', $html, $tags)) {
            return null;
        }

        foreach ($tags[0] as $tag) {
            if (! preg_match('~\bid\s*=\s*(["\x27])([a-z]+)\1~i', $tag, $idMatch)
                || strcasecmp($idMatch[2], $id) !== 0
                || ! preg_match('~\bvalue\s*=\s*(["\x27])([^\x22\x27]{1,8000})\1~i', $tag, $valueMatch)) {
                continue;
            }

            return html_entity_decode($valueMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function shareLandingFromDeeplinkHtml(string $html, string $original): ?string
    {
        $raw = $this->hiddenInputValue($html, 'deeplink');
        if ($raw === null) {
            return null;
        }

        $parts = parse_url($raw);
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'sheinlink'
            || strtolower((string) ($parts['host'] ?? '')) !== 'applink'
            || ($parts['path'] ?? '') !== '/share_receiver') {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $data = json_decode((string) ($query['data'] ?? ''), true);
        if (! is_array($data) || isset($data['share_type']) && $data['share_type'] !== 'cart') {
            return null;
        }

        return $this->canonicalShareLanding(
            (string) ($data['group_id'] ?? ''),
            $original,
            is_string($data['shc'] ?? null) ? $data['shc'] : null,
        );
    }

    private function trustedAppjumpFromHtml(string $html): ?string
    {
        $candidate = $this->hiddenInputValue($html, 'url');

        return $candidate !== null && $this->isTrustedAppjumpUrl($candidate)
            ? $candidate : null;
    }

    private function isTrustedAppjumpUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'api-shein.shein.com'
            && ($parts['path'] ?? '') === '/h5/sharejump/appjump'
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['port']);
    }

    private function shareLandingFromAppjumpHtml(string $html, string $original): ?string
    {
        if (strlen($html) > 2_000_000
            || ! preg_match('~\bshareInfo\s*=\s*(\{[^\r\n]{1,15000}\})~', $html, $matches)) {
            return null;
        }

        $info = json_decode($matches[1], true);
        if (! is_array($info)
            || (isset($info['share_type']) && $info['share_type'] !== 'cart')
            || (isset($info['status']) && is_numeric($info['status']) && (int) $info['status'] < 0)) {
            return null;
        }

        return $this->canonicalShareLanding((string) ($info['id'] ?? ''), $original);
    }

    private function canonicalShareLanding(string $groupId, string $original, ?string $embeddedShc = null): ?string
    {
        parse_str((string) (parse_url($original, PHP_URL_QUERY) ?: ''), $shortQuery);
        $shc = $shortQuery['shc'] ?? null;

        if (! preg_match('/^\d{6,20}$/D', $groupId)
            || ! is_string($shc) || ! preg_match('/^2_[A-Za-z0-9_-]{4,120}$/D', $shc)
            || ($embeddedShc !== null && $embeddedShc !== $shc)) {
            return null;
        }

        return 'https://www.shein.com/ar/cart/share/landing?'.http_build_query([
            'group_id' => $groupId,
            'shc' => $shc,
            'local_country' => 'AE',
            'cart_share' => '1',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Read an explicit cart identity only from a SHEIN shareInfo object or
     * a same-origin relative cart landing link that carries the ORIGINAL shc.
     *
     * A bare "group_id" in arbitrary HTML (including a hidden input with an
     * untrusted external URL) is not evidence that it belongs to this cart.
     */
    private function shareLandingFromHtml(string $html, string $original): ?string
    {
        if ($html === '' || strlen($html) > 2_000_000) {
            return null;
        }

        // Onelink may embed a flat JSON shareInfo object directly.
        if (preg_match_all('~<script\b[^>]*>([\s\S]{0,15000}?)</script>~i', $html, $scripts)) {
            foreach ($scripts[1] as $script) {
                if (! preg_match('~\bshareInfo\s*=\s*(\{[^\r\n]{1,15000}\})~', $script, $matches)) {
                    continue;
                }

                $info = json_decode($matches[1], true);
                if (! is_array($info)
                    || (isset($info['share_type']) && $info['share_type'] !== 'cart')
                    || (isset($info['status']) && is_numeric($info['status']) && (int) $info['status'] < 0)
                    || ! is_string($info['shc'] ?? null)) {
                    continue;
                }

                $groupId = $info['group_id'] ?? $info['groupId'] ?? null;
                if (is_string($groupId) || is_int($groupId)) {
                    $landing = $this->canonicalShareLanding((string) $groupId, $original, $info['shc']);
                    if ($landing !== null) {
                        return $landing;
                    }
                }
            }
        }

        // A relative cart landing URL embedded in a quoted JS string is
        // same-origin. An absolute https://example.com/... URL is never
        // accepted just because its query contains "group_id".
        if (! preg_match_all(
            '~["\x27](/(?:[a-z]{2}/)?cart/share/landing\?[^"\x27<>\s]{1,2048})["\x27]~i',
            $html,
            $links
        )) {
            return null;
        }

        foreach ($links[1] as $relative) {
            $decoded = rawurldecode(html_entity_decode($relative, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $parts = parse_url($decoded);
            if (! is_array($parts) || isset($parts['scheme']) || isset($parts['host'])
                || ! preg_match('~^/(?:[a-z]{2}/)?cart/share/landing$~i', (string) ($parts['path'] ?? ''))) {
                continue;
            }

            parse_str((string) ($parts['query'] ?? ''), $query);
            if (! is_string($query['shc'] ?? null)
                || ! (is_string($query['group_id'] ?? null) || is_int($query['group_id'] ?? null))) {
                continue;
            }

            $landing = $this->canonicalShareLanding((string) $query['group_id'], $original, $query['shc']);
            if ($landing !== null) {
                return $landing;
            }
        }

        return null;
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
