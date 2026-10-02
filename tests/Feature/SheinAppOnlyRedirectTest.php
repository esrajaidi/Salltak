<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SheinAppOnlyRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_only_redirect_stays_actionable_without_desktop_retry(): void
    {
        $user = User::factory()->create();
        Store::create(['name'=>'SHEIN','slug'=>'shein-app-only','domains'=>['shein.com'],
            'currency'=>'USD','adapter'=>'shein','is_active'=>true]);
        ExchangeRate::create(['currency'=>'USD','rate_to_lyd'=>7,'is_active'=>true]);

        Http::fake(['*' => Http::response('<html><body>app shell</body></html>', 200)]);
        Process::fake(['*' => Process::result(output: json_encode([
            'ok'=>true,'status'=>'app_only_redirect',
            'message'=>'رابط OneLink يفتح السلة في تطبيق SHEIN فقط، ولا يعرضها متصفح الخادم.',
            'final_url'=>'https://m.shein.com/ar/',
            'items'=>[], 'payloads'=>[],
        ]))]);

        $this->actingAs($user)->post('/my-carts/analyze', [
            'source_url'=>'https://onelink.shein.com/55/example?shc=SHARE_A',
        ])->assertOk()->assertSee('رابط OneLink يفتح السلة في تطبيق SHEIN فقط')
          ->assertSee('جلب سلة من جديد')
          ->assertSee('رابط المشاركة يفتح داخل تطبيق SHEIN')
          ->assertSee('فتح رابط المشاركة على الهاتف');

        Process::assertRanTimes(fn () => true, 1);
    }
}
