<?php

namespace Tests\Unit;

use App\Services\CartImport\Browser\SheinImportedItemCleaner;
use PHPUnit\Framework\TestCase;

class SheinImportedItemCleanerTest extends TestCase
{
    public function test_it_preserves_protocol_relative_shein_product_images_as_https(): void
    {
        $items = [[
            'external_id' => '123',
            'name' => 'SHEIN product',
            'image_url' => '//img.ltwebstatic.com/images3_pi/2026/test.webp',
        ]];

        $cleaned = SheinImportedItemCleaner::clean($items);

        $this->assertSame(
            'https://img.ltwebstatic.com/images3_pi/2026/test.webp',
            $cleaned[0]['image_url']
        );
    }

    public function test_it_rejects_product_page_urls_that_are_not_images(): void
    {
        $items = [[
            'external_id' => '123',
            'name' => 'SHEIN product',
            'image_url' => 'https://m.shein.com/ar/Women-Dresses-p-123.html',
        ]];

        $cleaned = SheinImportedItemCleaner::clean($items);

        $this->assertSame('', $cleaned[0]['image_url']);
    }

    public function test_it_uses_a_valid_fallback_image_when_primary_image_is_invalid(): void
    {
        $items = [[
            'external_id' => '123',
            'name' => 'SHEIN product',
            'image_url' => 'https://m.shein.com/ar/Women-Dresses-p-123.html',
        ]];

        $fallback = [[
            'external_id' => '123',
            'name' => 'SHEIN product',
            'image_url' => '//img.ltwebstatic.com/images3_pi/2026/fallback.jpg',
        ]];

        $cleaned = SheinImportedItemCleaner::clean($items, $fallback);

        $this->assertSame(
            'https://img.ltwebstatic.com/images3_pi/2026/fallback.jpg',
            $cleaned[0]['image_url']
        );
    }

    public function test_it_still_rejects_placeholder_images(): void
    {
        $items = [[
            'external_id' => '123',
            'name' => 'SHEIN product',
            'image_url' => '//img.ltwebstatic.com/assets/placeholder.webp',
        ]];

        $cleaned = SheinImportedItemCleaner::clean($items);

        $this->assertSame('', $cleaned[0]['image_url']);
    }
}
