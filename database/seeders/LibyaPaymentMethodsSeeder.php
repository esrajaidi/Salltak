<?php

namespace Database\Seeders;

use App\Services\LibyaPaymentMethodCatalog;
use Illuminate\Database\Seeder;

class LibyaPaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        app(LibyaPaymentMethodCatalog::class)->sync();
    }
}
