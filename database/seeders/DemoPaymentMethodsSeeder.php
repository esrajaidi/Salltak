<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class DemoPaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || ! env('DEMO_PAYMENT_METHODS', true)) {
            return;
        }

        $lypay = PaymentMethod::where('code', 'lypay')->first();
        if ($lypay) {
            $lypay->update([
                'is_active' => true,
                'instructions' => 'بيانات تجريبية فقط — لا تستخدمها لإجراء دفع حقيقي. في التشغيل الفعلي استبدلها ببيانات LYPay الخاصة بالتاجر من لوحة التحكم.',
                'config' => array_replace($lypay->config ?? [], [
                    'integration_mode' => 'manual_verification',
                    'proof_mode' => 'reference_or_receipt',
                    'availability' => 'online',
                    'allow_deposit' => '1',
                    'allow_balance' => '1',
                    'test_mode' => '1',
                    'bank_name' => 'مصرف تجريبي',
                    'account_name' => 'حساب سلتك التجريبي - غير مخصص للدفع الحقيقي',
                    'iban' => 'LY00DEMO0000000000000000',
                    'merchant_name' => 'سلتك - بيئة تجريبية',
                    'qr_value' => 'DEMO-LYPAY-NOT-FOR-REAL-PAYMENTS',
                ]),
            ]);
        }

        $cash = PaymentMethod::where('code', 'cash')->first();
        if ($cash) {
            $cash->update([
                'is_active' => true,
                'config' => array_replace($cash->config ?? [], [
                    'integration_mode' => 'cash',
                    'proof_mode' => 'none',
                    'availability' => 'all',
                    'allow_deposit' => '1',
                    'allow_balance' => '1',
                    'collection_location' => 'مكتب سلتك - بيانات تجريبية',
                    'collection_phone' => '0910000000',
                ]),
            ]);
        }
    }
}
