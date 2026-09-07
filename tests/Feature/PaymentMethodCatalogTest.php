<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use App\Models\PaymentMethod;
use App\Services\LibyaPaymentMethodCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_libya_catalog_installs_all_supported_entries_inactive_by_default(): void
    {
        $result = app(LibyaPaymentMethodCatalog::class)->sync();

        $this->assertSame(23, $result['total']);
        $this->assertSame(23, PaymentMethod::count());
        $this->assertDatabaseHas('payment_methods', ['code' => 'lypay', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'onepay', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'visa', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'mastercard', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'runpay_wallet', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'moamalat_cards', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'cash', 'is_active' => false]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'cash_on_delivery', 'is_active' => false]);
    }

    public function test_catalog_refresh_preserves_admin_activation_and_merchant_configuration(): void
    {
        app(LibyaPaymentMethodCatalog::class)->sync();
        $method = PaymentMethod::where('code', 'lypay')->firstOrFail();
        $method->update([
            'is_active' => true,
            'min_amount' => 25,
            'config' => array_merge($method->config ?? [], [
                'merchant_id' => 'MERCHANT-123',
                'iban' => 'LY00TEST',
            ]),
        ]);

        app(LibyaPaymentMethodCatalog::class)->sync();
        $method->refresh();

        $this->assertTrue($method->is_active);
        $this->assertSame(25.0, (float) $method->min_amount);
        $this->assertSame('MERCHANT-123', $method->config['merchant_id']);
        $this->assertSame('LY00TEST', $method->config['iban']);
    }

    public function test_lypay_requires_receiver_data_before_activation(): void
    {
        app(LibyaPaymentMethodCatalog::class)->sync();
        $method = PaymentMethod::where('code', 'lypay')->firstOrFail();

        $this->assertNotEmpty($method->activationIssues());

        $method->update(['config' => array_merge($method->config ?? [], ['iban' => 'LY001234567890'])]);
        $this->assertSame([], $method->fresh()->activationIssues());
    }

    public function test_external_card_methods_require_acquirer_merchant_and_checkout_url(): void
    {
        app(LibyaPaymentMethodCatalog::class)->sync();
        $method = PaymentMethod::where('code', 'visa')->firstOrFail();

        $this->assertNotEmpty($method->activationIssues());

        $method->update(['config' => array_merge($method->config ?? [], [
            'acquirer_name' => 'Licensed Acquirer',
            'merchant_id' => 'M-100',
            'checkout_url' => 'https://payments.example.test/checkout',
        ])]);

        $this->assertSame([], $method->fresh()->activationIssues());
    }
    public function test_demo_database_seeder_enables_lypay_and_cash_with_obvious_fake_lypay_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $lypay = PaymentMethod::where('code','lypay')->firstOrFail();
        $cash = PaymentMethod::where('code','cash')->firstOrFail();

        $this->assertTrue($lypay->is_active);
        $this->assertTrue($cash->is_active);
        $this->assertStringStartsWith('LY00DEMO', (string)($lypay->config['iban'] ?? ''));
        $this->assertSame('1', (string)($lypay->config['test_mode'] ?? '0'));
        $this->assertSame([], $lypay->activationIssues());
    }

}
