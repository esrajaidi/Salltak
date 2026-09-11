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

        $current = $url;

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
                return $current;
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

        return $current;
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
