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
        $store = $this->detectStore($url);
        $adapter = $this->shein->canHandle($url, $store) ? $this->shein : $this->generic;
        $result = $adapter->import($url, $store);

        return ['store' => $store, 'result' => $result];
    }
}
