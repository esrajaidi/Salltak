<?php

namespace App\Services\CartImport;

use App\Models\Store;
use App\Services\CartImport\Adapters\GenericProductAdapter;
use App\Services\CartImport\Adapters\SheinShareAdapter;
use App\Services\StoreUrlClassifier;

class CartImportService
{
    public function __construct(
        private readonly StoreUrlClassifier $classifier,
        private readonly SheinUrlResolver $sheinUrlResolver,
        private readonly SheinShareAdapter $shein,
        private readonly GenericProductAdapter $generic,
    ) {}

    public function detectStore(string $url): ?Store
    {
        $host = $this->classifier->host($url);
        return Store::query()->where('is_active', true)->get()->first(
            fn (Store $store) => $this->classifier->domainMatches($host, $store->domains ?? [])
        );
    }

    public function import(string $url): array
    {
        $sourceUrl = $url;
        $resolvedUrl = $this->sheinUrlResolver->resolve($sourceUrl);

        $store = $this->detectStore($resolvedUrl) ?? $this->detectStore($sourceUrl);
        $adapter = $this->shein->canHandle($resolvedUrl, $store) ? $this->shein : $this->generic;
        $result = $adapter->import($resolvedUrl, $store);

        return [
            'store' => $store,
            'result' => $result,
            'source_url' => $sourceUrl,
            'resolved_url' => $resolvedUrl,
        ];
    }
}
