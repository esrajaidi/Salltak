<?php

namespace Tests\Unit;

use App\Services\CartImport\Browser\SheinBrowserImporter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SheinWwwPriceSourceTest extends TestCase
{
    private function enforce(array $result): array
    {
        $class = new ReflectionClass(SheinBrowserImporter::class);
        $importer = $class->newInstanceWithoutConstructor();

        return $class->getMethod('requireWwwPriceEvidence')->invoke($importer, $result);
    }

    public function test_www_bff_prices_are_allowed_but_still_unverified_for_account_coupon(): void
    {
        $result = $this->enforce([
            'status' => 'loaded',
            'items' => [['external_id' => '123', 'unit_price_original' => 12.50]],
            'final_url' => 'https://m.shein.com/ar/cart/share/landing',
            'meta' => ['bound_bff_response_host' => 'www.shein.com', 'trusted_source' => 'bound_share_bff', 'account_session_applied' => true],
        ]);

        $this->assertCount(1, $result['items']);
        $this->assertSame('www.shein.com', $result['meta']['price_source_verified_host']);
        $this->assertFalse($result['meta']['account_price_verified']);
    }

    public function test_mobile_bff_prices_are_rejected_even_when_html_page_is_www(): void
    {
        $result = $this->enforce([
            'status' => 'loaded',
            'items' => [['external_id' => '123', 'unit_price_original' => 12.50]],
            'final_url' => 'https://www.shein.com/ar/cart/share/landing',
            'meta' => [
                'bound_bff_response_host' => 'm.shein.com',
                'account_session_applied' => true,
                'dom_item_count' => 4,
            ],
        ]);

        $this->assertSame([], $result['items']);
        $this->assertSame('www_price_source_unavailable', $result['status']);
        $this->assertTrue($result['meta']['rejected_non_www_prices']);
        $this->assertFalse($result['meta']['account_price_verified']);
    }

    public function test_mobile_page_prices_are_rejected_without_a_www_bff(): void
    {
        $result = $this->enforce([
            'status' => 'ae_price_unverified',
            'items' => [['external_id' => '123', 'unit_price_original' => 11]],
            'final_url' => 'https://m.shein.com/cart/share/landing',
            'meta' => ['dom_item_count' => 1, 'account_session_applied' => true],
        ]);

        $this->assertSame([], $result['items']);
        $this->assertSame('www_price_source_unavailable', $result['status']);
    }

    public function test_www_page_price_can_be_shown_as_unverified_storefront_quote(): void
    {
        $result = $this->enforce([
            'status' => 'ae_price_unverified',
            'items' => [['external_id' => '123', 'unit_price_original' => 15.20]],
            'final_url' => 'https://www.shein.com/ar/cart/share/landing',
            'meta' => ['dom_item_count' => 1],
        ]);

        $this->assertCount(1, $result['items']);
        $this->assertSame('www.shein.com', $result['meta']['price_source_verified_host']);
        $this->assertFalse($result['meta']['account_price_verified']);
    }

    public function test_www_page_prices_are_blocked_if_owner_session_was_not_applied(): void
    {
        $result = $this->enforce([
            'status' => 'loaded',
            'items' => [['external_id' => '123', 'unit_price_original' => 11.25]],
            'final_url' => 'https://www.shein.com/ar/cart/share/landing',
            'meta' => ['bound_bff_response_host' => 'www.shein.com', 'account_session_applied' => false],
        ]);

        $this->assertSame([], $result['items']);
        $this->assertSame('account_session_required', $result['status']);
        $this->assertFalse($result['meta']['account_price_verified']);
    }

    public function test_no_items_are_not_misclassified_as_wrong_host_prices(): void
    {
        $result = $this->enforce([
            'status' => 'shared_page_unreadable', 'items' => [],
            'final_url' => '',
            'meta' => [],
        ]);

        $this->assertSame('shared_page_unreadable', $result['status']);
        $this->assertSame([], $result['items']);
    }
}
