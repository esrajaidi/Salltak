<?php

namespace App\Services\CartImport\Browser;

class SheinImportedItemCleaner
{
    public static function clean(array $items, array $fallbackItems = []): array
    {
        $fallbackById = [];
        $fallbackByUrl = [];

        foreach ($fallbackItems as $fallback) {
            if (! is_array($fallback)) {
                continue;
            }

            $id = trim((string) ($fallback['external_id'] ?? ''));
            if ($id !== '') {
                $fallbackById[$id] = $fallback;
            }

            $url = self::normalizeUrl((string) ($fallback['product_url'] ?? ''));
            if ($url !== '') {
                $fallbackByUrl[$url] = $fallback;
            }
        }

        $sameCount = count($items) > 0 && count($items) === count($fallbackItems);

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = trim((string) ($item['external_id'] ?? ''));
            $url = self::normalizeUrl((string) ($item['product_url'] ?? ''));
            $fallback = $id !== '' ? ($fallbackById[$id] ?? []) : [];
            if ($fallback === [] && $url !== '') {
                $fallback = $fallbackByUrl[$url] ?? [];
            }
            if ($fallback === [] && $sameCount && is_array($fallbackItems[$index] ?? null)) {
                $fallback = $fallbackItems[$index];
            }

            $name = self::cleanName((string) ($item['name'] ?? ''));
            if ($name === '' || self::isGenericSharedLabel($name)) {
                $fallbackName = self::cleanName((string) ($fallback['name'] ?? ''));
                $name = $fallbackName !== '' && ! self::isGenericSharedLabel($fallbackName)
                    ? $fallbackName
                    : ($id !== '' ? 'منتج SHEIN #'.$id : 'منتج SHEIN');
            }

            $image = self::cleanImage((string) ($item['image_url'] ?? ''));
            if ($image === '') {
                $image = self::cleanImage((string) ($fallback['image_url'] ?? ''));
            }

            $item['name'] = $name;
            $item['image_url'] = $image;
            $items[$index] = $item;
        }

        return array_values($items);
    }

    public static function needsEnrichment(array $items): bool
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = self::cleanName((string) ($item['name'] ?? ''));
            if ($name === '' || self::isGenericSharedLabel($name)) {
                return true;
            }

            if (self::cleanImage((string) ($item['image_url'] ?? '')) === '') {
                return true;
            }
        }

        return false;
    }

    private static function cleanName(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = preg_replace('/<[^>]*>/u', ' ', $decoded) ?? $decoded;
        $decoded = preg_replace('/\s+/u', ' ', $decoded) ?? $decoded;

        return trim($decoded);
    }

    private static function isGenericSharedLabel(string $value): bool
    {
        return (bool) preg_match('/items\s+shared\s+by|shared\s+items|add\s+all\s+to\s+cart|العناصر\s+التي\s+تمت\s+مشاركتها|العناصر\s+المشتركة|إضافة\s+الكل|اضافة\s+الكل/iu', $value);
    }

    private static function cleanImage(string $value): string
    {
        $value = trim($value);
        if (! preg_match('#^https?://#i', $value)) {
            return '';
        }

        if (preg_match('/placeholder|default[-_]?image|no[-_]?image|transparent|spacer|blank|logo|icon|avatar|badge/i', $value)) {
            return '';
        }

        return $value;
    }

    private static function normalizeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return preg_replace('/[?#].*$/', '', $value) ?? $value;
    }
}
