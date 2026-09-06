<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\ExchangeRate;
use App\Models\Store;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => env('DEMO_ADMIN_EMAIL', 'admin@cartly.test')],
            ['name' => 'مدير المنصة', 'phone' => '0910000000', 'password' => Hash::make(env('DEMO_ADMIN_PASSWORD', 'Admin@123456')), 'role' => 'admin', 'is_active' => true]
        );

        $customer = User::updateOrCreate(
            ['email' => env('DEMO_CUSTOMER_EMAIL', 'customer@cartly.test')],
            ['name' => 'عميل تجريبي', 'phone' => '0920000000', 'password' => Hash::make(env('DEMO_CUSTOMER_PASSWORD', 'Customer@123')), 'role' => 'customer', 'is_active' => true]
        );

        $shein = Store::updateOrCreate(['slug' => 'shein'], [
            'name' => 'SHEIN', 'domains' => ['shein.com', 'onelink.shein.com'], 'currency' => 'USD',
            'adapter' => 'shein', 'is_active' => true,
        ]);
        foreach ([
            ['temu','Temu',['temu.com'],'USD'],
            ['aliexpress','AliExpress',['aliexpress.com'],'USD'],
            ['amazon','Amazon',['amazon.com'],'USD'],
            ['trendyol','Trendyol',['trendyol.com'],'TRY'],
        ] as [$slug,$name,$domains,$currency]) {
            Store::updateOrCreate(['slug'=>$slug], ['name'=>$name,'domains'=>$domains,'currency'=>$currency,'adapter'=>'generic','is_active'=>false]);
        }

        foreach (['USD'=>7.00,'EUR'=>8.10,'AED'=>1.91,'TRY'=>0.18,'SAR'=>1.87] as $currency=>$rate) {
            ExchangeRate::updateOrCreate(['currency'=>$currency], ['rate_to_lyd'=>$rate,'is_active'=>true,'updated_by'=>$admin->id]);
        }

        foreach ([
            'platform_name' => 'سلات ليبيا',
            'home_intro' => 'احفظ سلة مشترياتك واعرف قيمتها بالدينار الليبي.',
            'contact_email' => 'support@cartly.test',
        ] as $key=>$value) SystemSetting::updateOrCreate(['key'=>$key], ['value'=>$value]);

        if (! Cart::where('user_id',$customer->id)->exists()) {
            $cart = Cart::create([
                'user_id'=>$customer->id,'store_id'=>$shein->id,'source_url'=>'https://m.shein.com/ar/cart/share/landing?shc=demo&group_id=demo&local_country=AE&cart_share=1',
                'source_host'=>'m.shein.com','source_currency'=>'AED','exchange_rate'=>1.91,'subtotal_original'=>35,
                'total_lyd'=>66.85,'status'=>'saved','import_status'=>'success','import_message'=>'سلة تجريبية',
            ]);
            $cart->items()->createMany([
                ['name'=>'حقيبة تجريبية','quantity'=>1,'unit_price_original'=>20,'line_total_original'=>20,'currency'=>'AED'],
                ['name'=>'قميص تجريبي','quantity'=>1,'unit_price_original'=>15,'line_total_original'=>15,'currency'=>'AED'],
            ]);
        }
    }
}
