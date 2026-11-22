<?php

namespace Tests\Unit;

use App\Services\CartImport\SheinSharedCartInput;
use PHPUnit\Framework\TestCase;

final class SheinGroupIdentityTest extends TestCase
{
    public function test_both_numeric_and_opaque_ids_are_accepted_without_mutation(): void
    {
        foreach (['941308490', 'gIz'.str_repeat('Ab9_-', 30).'='] as $group) {
            $url = 'https://www.shein.com/ar/cart/share/landing?'.http_build_query(['group_id' => $group, 'shc' => '2_TestToken']);
            $this->assertTrue(SheinSharedCartInput::isValidGroupId($group));
            $this->assertSame($url, SheinSharedCartInput::extract($url));
        }
    }

    public function test_invalid_group_values_are_rejected(): void
    {
        foreach (['invalid', str_repeat('A', 1027), str_repeat('A', 32).'<', [], null] as $group) {
            $this->assertFalse(SheinSharedCartInput::isValidGroupId($group));
        }
    }
}
