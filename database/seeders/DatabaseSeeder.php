<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\DepositRule;
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
        $this->call(SiteContentSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => env('DEMO_ADMIN_EMAIL', 'admin@cartly.test')],
            ['name' => 'مدير المنصة', 'phone' => '0910000000', 'password' => Hash::make(env('DEMO_ADMIN_PASSWORD', 'Admin@123456')), 'role' => 'admin', 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => env('DEMO_MANAGER_EMAIL', 'manager@cartly.test')],
            ['name' => 'مسؤول الطلبات', 'phone' => '0911000000', 'password' => Hash::make(env('DEMO_MANAGER_PASSWORD', 'Manager@123456')), 'role' => 'order_manager', 'is_active' => true]
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
            'platform_name' => 'سلتك',
            'home_intro' => 'احفظ سلة مشترياتك، أرسلها للمراجعة، وادفع عربونًا أو دفعات جزئية حتى التسليم.',
            'contact_email' => 'support@cartly.test',
            'notification_emails' => env('DEMO_ADMIN_EMAIL', 'admin@cartly.test'),
            'notify_email_new_order' => '1',
            'notify_email_new_message' => '1',
            'notify_email_payment' => '1',
            'notify_email_customer_updates' => '1',
        ] as $key=>$value) SystemSetting::updateOrCreate(['key'=>$key], ['value'=>$value]);

        $this->call(LibyaPaymentMethodsSeeder::class);

        $this->call(DemoPaymentMethodsSeeder::class);


        foreach ([
            ['name'=>'أقل من 200 د.ل','min_total'=>0,'max_total'=>199.99,'type'=>'percentage','value'=>30,'sort_order'=>10],
            ['name'=>'من 200 إلى 500 د.ل','min_total'=>200,'max_total'=>500,'type'=>'percentage','value'=>40,'sort_order'=>20],
            ['name'=>'أكثر من 500 د.ل','min_total'=>500.01,'max_total'=>null,'type'=>'percentage','value'=>50,'sort_order'=>30],
        ] as $rule) DepositRule::updateOrCreate(['name'=>$rule['name']], array_merge($rule,['is_active'=>true]));

        if (! Cart::where('user_id',$customer->id)->exists()) {
            $cart = Cart::create([
                'user_id'=>$customer->id,'store_id'=>$shein->id,'source_url'=>'https://m.shein.com/ar/cart/share/landing?shc=demo&group_id=demo&local_country=AE&cart_share=1',
                'source_host'=>'m.shein.com','source_currency'=>'USD','exchange_rate'=>7.00,'subtotal_original'=>9.32,
                'total_lyd'=>65.24,'status'=>'saved','import_status'=>'success','import_message'=>'سلة تجريبية',
            ]);
            $cart->items()->createMany([
                ['name'=>'حقيبة تجريبية','color'=>'أسود','size'=>'One Size','variant'=>'DEMO-BAG','quantity'=>1,'unit_price_original'=>5.33,'line_total_original'=>5.33,'currency'=>'USD'],
                ['name'=>'قميص تجريبي','color'=>'أبيض','size'=>'XL','variant'=>'DEMO-SHIRT','quantity'=>1,'unit_price_original'=>3.99,'line_total_original'=>3.99,'currency'=>'USD'],
            ]);
        }
    }
}
