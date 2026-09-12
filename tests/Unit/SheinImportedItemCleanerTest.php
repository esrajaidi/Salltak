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
