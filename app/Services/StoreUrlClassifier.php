<?php

namespace App\Services;

class StoreUrlClassifier
{
    public function host(string $url): string
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }

    public function isShein(string $url): bool
    {
        $host = $this->host($url);
        return $host === 'onelink.shein.com' || str_ends_with($host, '.shein.com') || $host === 'shein.com';
    }

    public function domainMatches(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }
        return false;
    }
}
