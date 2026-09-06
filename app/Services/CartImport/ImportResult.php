<?php

namespace App\Services\CartImport;

class ImportResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly array $items = [],
        public readonly ?string $currency = null,
        public readonly array $meta = [],
    ) {}

    public static function success(array $items, ?string $currency = null, array $meta = []): self
    {
        return new self('success', 'تم جلب المنتجات بنجاح.', $items, $currency, $meta);
    }

    public static function needsReview(string $message, array $items = [], ?string $currency = null, array $meta = []): self
    {
        return new self('needs_review', $message, $items, $currency, $meta);
    }

    public static function failed(string $message, array $meta = []): self
    {
        return new self('failed', $message, [], null, $meta);
    }
}
