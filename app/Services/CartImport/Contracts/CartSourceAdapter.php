<?php

namespace App\Services\CartImport\Contracts;

use App\Models\Store;
use App\Services\CartImport\ImportResult;

interface CartSourceAdapter
{
    public function canHandle(string $url, ?Store $store = null): bool;
    public function import(string $url, ?Store $store = null): ImportResult;
}
