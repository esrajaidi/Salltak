<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CartImport\Browser\SheinBrowserImporter;
use App\Services\CartImport\SheinSessionVault;
use App\Services\CartImport\SheinSharedCartInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;

class SheinAccountSessionController extends Controller
{
    public function __construct(private readonly SheinSessionVault $vault) {}

    public function index()
    {
        return view('admin.shein-session.index', ['connection' => $this->vault->info()]);
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'admin_password' => ['required','string'],
            'storage_state' => ['required','file','max:256','extensions:json'],
        ]);
        $this->verifyAdminPassword($request, $data['admin_password']);
        $content = file_get_contents($data['storage_state']->getRealPath());
        try {
            $state = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['storage_state' => 'ملف جلسة SHEIN غير صالح.']);
        }
        if (! is_array($state)) {
            throw ValidationException::withMessages(['storage_state' => 'ملف جلسة SHEIN غير صالح.']);
        }

        $this->vault->save($state, $request->user()->id);
        return back()->with('success', 'تم حفظ الجلسة مشفرة. الاستعمال الآلي متوقف حتى تختبري الأسعار وتفعّليه.');
    }

    public function test(Request $request, SheinBrowserImporter $browser)
    {
        $data = $request->validate([
            'test_url' => ['required','string','max:2500'],
        ]);
        try {
            $shareUrl = SheinSharedCartInput::extract($data['test_url']);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['test_url' => $exception->getMessage()]);
        }
        if ($this->vault->read(true) === null) {
            throw ValidationException::withMessages(['test_url' => 'ارفعي جلسة حسابك أولًا.']);
        }

        // This request alone may use the encrypted session while the public flow remains off.
        $result = $browser->import($shareUrl, true);
        $rows = is_array($result['items'] ?? null) ? $result['items'] : [];
        $item = $rows[0] ?? [];
        return back()->with('shein_session_test_result', [
            'status' => (string) ($result['status'] ?? 'failed'),
            'count' => count($rows),
            'first_price_usd' => $item['unit_price_original'] ?? null,
            'account_session_applied' => (bool) ($result['meta']['account_session_applied'] ?? false),
            'ae_price_verified' => (bool) ($result['meta']['pricing_country_verified'] ?? false),
        ]);
    }

    /**
     * Diagnostic only: compare the same SHEIN product on www with and without
     * the owner-provided session. Nothing here updates customer/cart prices.
     */
    public function probeProductPrice(Request $request)
    {
        $data = $request->validate([
            'product_url' => ['required', 'url:http,https', 'max:2000'],
        ]);
        $url = trim($data['product_url']);
        $parsed = parse_url($url);
        if (! is_array($parsed)
            || strtolower((string) ($parsed['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parsed['host'] ?? '')) !== 'www.shein.com'
            || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['port'])
            || ! preg_match('~(?:^|[-/])p-\d{4,20}\.html$~i', (string) ($parsed['path'] ?? ''))) {
            throw ValidationException::withMessages([
                'product_url' => 'الصقي رابط منتج SHEIN من www.shein.com ينتهي بـ -p-رقم.html.',
            ]);
        }

        $state = $this->vault->read(true);
        if (! $state) {
            throw ValidationException::withMessages([
                'product_url' => 'ارفعي جلسة SHEIN المحفوظة من www.shein.com أولًا.',
            ]);
        }

        $script = base_path('scripts/shein-www-account-price-probe.mjs');
        if (! is_file($script)) {
            return back()->with('shein_price_probe_result', ['status' => 'unavailable']);
        }

        $payload = json_encode(['url' => $url, 'accountSession' => $state], JSON_THROW_ON_ERROR);
        $process = Process::timeout(105)->input($payload)->run([
            (string) config('services.cart_import.shein_browser.node_binary', 'node'), $script,
        ]);
        $result = json_decode($process->output(), true);
        if (! is_array($result) || ($result['status'] ?? '') !== 'probed') {
            return back()->with('shein_price_probe_result', [
                'status' => (string) ($result['status'] ?? 'failed'),
                'message' => 'تعذر مقارنة الأسعار؛ قد يطلب SHEIN إعادة تسجيل الدخول أو يمنع القراءة الآلية.',
            ]);
        }

        $cleanObservation = static function (mixed $result): array {
            if (! is_array($result)) return ['status' => 'failed', 'prices' => []];
            $prices = [];
            foreach (array_slice((array) ($result['visibleUsdCandidates'] ?? []), 0, 8) as $price) {
                if (is_array($price) && is_numeric($price['value'] ?? null)) {
                    $amount = (float) $price['value'];
                    if ($amount > 0 && $amount < 20000) $prices[] = $amount;
                }
            }
            return [
                'status' => (string) ($result['status'] ?? 'failed'),
                'final_host' => (string) ($result['finalHost'] ?? ''),
                'prices' => $prices,
            ];
        };

        return back()->with('shein_price_probe_result', [
            'status' => 'probed',
            'owner' => $cleanObservation($result['owner'] ?? []),
            'guest' => $cleanObservation($result['guest'] ?? []),
            'confirmed' => false,
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['confirm' => ['required','accepted']]);
        $this->vault->enable(true);
        return back()->with('success', 'تم تفعيل الاستيراد بجلسة حساب الشراء. تابعي تطابق الأسعار باستمرار.');
    }

    public function disable()
    {
        $this->vault->enable(false);
        return back()->with('success', 'تم تعطيل استعمال جلسة SHEIN فورًا.');
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['admin_password' => ['required','string']]);
        $this->verifyAdminPassword($request, $data['admin_password']);
        $this->vault->delete();
        return back()->with('success', 'تم حذف جلسة SHEIN المخزنة.');
    }

    private function verifyAdminPassword(Request $request, string $password): void
    {
        $key = 'shein-session-password:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['admin_password' => 'محاولات كثيرة. حاولي لاحقًا.']);
        }
        if (! Hash::check($password, (string) $request->user()->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['admin_password' => 'كلمة مرور مدير سلتك غير صحيحة.']);
        }
        RateLimiter::clear($key);
    }
}
