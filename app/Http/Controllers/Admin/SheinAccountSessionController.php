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
            'request_failure' => match ($result['meta']['direct_bff_error_category'] ?? '') {
                'timeout' => 'انتهت مهلة الاتصال بخدمة السلة',
                'dns' => 'تعذر العثور على عنوان خدمة السلة (DNS)',
                'tls' => 'فشل التحقق من شهادة الاتصال الآمن (HTTPS)',
                'connection_reset' => 'انقطع الاتصال بخدمة السلة',
                'connection_refused' => 'رُفض الاتصال بخدمة السلة',
                'context_closed' => 'أُغلق المتصفح قبل اكتمال الطلب',
                'unknown' => 'فشل اتصال غير مصنف',
                default => null,
            },
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

        // A product page may hydrate asynchronously. PHP's built-in server
        // defaults to 30 seconds, which can terminate the request while
        // Playwright is still comparing two separate browser contexts.
        if (function_exists('set_time_limit')) {
            set_time_limit(115);
        }

        $payload = json_encode(['url' => $url, 'accountSession' => $state], JSON_THROW_ON_ERROR);
        try {
            $process = Process::timeout(95)->input($payload)->run([
                (string) config('services.cart_import.shein_browser.node_binary', 'node'), $script,
            ]);
            $result = json_decode($process->output(), true);
        } catch (\Throwable) {
            // Never include the process input, its command line or the
            // session state in error pages, logs or validation messages.
            return back()->with('shein_price_probe_result', [
                'status' => 'timeout',
                'message' => 'انتهت مهلة قراءة السعر من SHEIN. لا تعيدي الضغط بسرعة؛ جرّبي مرة أخرى لاحقًا.',
            ]);
        }
        if (! is_array($result) || ($result['status'] ?? '') !== 'probed') {
            return back()->with('shein_price_probe_result', [
                'status' => (string) ($result['status'] ?? 'failed'),
                'message' => 'تعذر مقارنة الأسعار؛ قد يطلب SHEIN إعادة تسجيل الدخول أو يمنع القراءة الآلية.',
            ]);
        }

        $cleanObservation = static function (mixed $result): array {
            if (! is_array($result)) return ['status' => 'failed', 'prices' => [], 'indicators' => []];
            $prices = [];
            foreach (array_slice((array) ($result['visibleUsdCandidates'] ?? []), 0, 8) as $price) {
                if (is_array($price) && is_numeric($price['value'] ?? null)) {
                    $amount = (float) $price['value'];
                    if ($amount > 0 && $amount < 20000) {
                        $source = (string) ($price['source'] ?? '');
                        $prices[] = [
                            'value' => $amount,
                            'source' => in_array($source, [
                                'product_price_dom', 'possible_price_dom',
                                'product_price_metadata', 'public_structured_data',
                            ], true) ? $source : 'unverified',
                        ];
                    }
                }
            }
            $indicators = is_array($result['indicators'] ?? null) ? $result['indicators'] : [];
            return [
                'status' => (string) ($result['status'] ?? 'failed'),
                'final_host' => (string) ($result['finalHost'] ?? ''),
                'http_status' => (int) ($result['httpStatus'] ?? 0),
                'prices' => $prices,
                'indicators' => [
                    'product_intro_present' => (bool) ($indicators['productIntroPresent'] ?? false),
                    'price_node_count' => max(0, min(1000, (int) ($indicators['domPriceNodeCount'] ?? 0))),
                    'usd_visible' => (bool) ($indicators['usdVisibleInPage'] ?? false),
                    'non_usd_currency_visible' => (bool) ($indicators['nonUsdCurrencyVisible'] ?? false),
                    'security_check_possible' => (bool) ($indicators['challengeLikely'] ?? false),
                ],
            ];
        };

        return back()->with('shein_price_probe_result', [
            'status' => 'probed',
            'owner' => $cleanObservation($result['owner'] ?? []),
            'guest' => $cleanObservation($result['guest'] ?? []),
            'confirmed' => false,
        ]);
    }

    /**
     * Read-only preflight for future automated purchaser-cart quoting.
     * Never clicks Add to Cart, applies a coupon or visits payment.
     */
    public function probeCartReadiness()
    {
        $state = $this->vault->read(true);
        if ($state === null) {
            return back()->with('shein_cart_readiness_result', [
                'status' => 'no_owner_session',
            ]);
        }

        $worker = base_path('scripts/shein-owner-cart-readiness.mjs');
        if (! is_file($worker)) {
            return back()->with('shein_cart_readiness_result', [
                'status' => 'probe_unavailable',
            ]);
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(60);
        }

        try {
            $result = Process::path(base_path())
                ->timeout(40)
                ->input(json_encode(['accountSession' => $state], JSON_THROW_ON_ERROR))
                ->run([
                    (string) config('services.cart_import.shein_browser.node_binary', 'node'),
                    $worker,
                ]);
            $decoded = json_decode($result->output(), true);
        } catch (\Throwable) {
            $decoded = null;
        }

        // Only return safe, enum-constrained fields. Never return raw process
        // errors or page text because the stored session is a bearer secret.
        $statuses = [
            'no_owner_session', 'redirected_or_non_cart_page',
            'access_limited', 'login_required', 'empty_cart_observed',
            'existing_cart_must_be_preserved', 'cart_state_unclear',
            'probe_failed', 'probe_unavailable',
        ];
        $status = is_array($decoded) ? ($decoded['status'] ?? '') : '';
        $status = in_array($status, $statuses, true) ? $status : 'probe_failed';
        $count = is_array($decoded) ? ($decoded['cart_item_count_visible'] ?? null) : null;

        return back()->with('shein_cart_readiness_result', [
            'status' => $status,
            'cart_item_count_visible' => is_int($count) && $count >= 0 && $count <= 1000 ? $count : null,
            'coupon_label_seen' => ($decoded['coupon_label_seen'] ?? false) === true,
            'ae_region_visible' => ($decoded['ae_region_visible'] ?? false) === true,
            'usd_currency_visible' => ($decoded['usd_currency_visible'] ?? false) === true,
            'account_cart_modified' => false,
            'final_checkout_price_verified' => false,
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
