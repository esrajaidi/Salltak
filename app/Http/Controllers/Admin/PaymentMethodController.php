<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\LibyaPaymentMethodCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return view('admin.payment-methods.index', [
            'methods' => PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['config'] = $this->config($request);
        PaymentMethod::create($data);

        return back()->with('success', 'تمت إضافة طريقة الدفع.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $this->validated($request, $paymentMethod);
        $data['config'] = $this->config($request, $paymentMethod);
        $paymentMethod->update($data);
        $paymentMethod->refresh();

        if ($paymentMethod->is_active && ($issues = $paymentMethod->activationIssues())) {
            $paymentMethod->update(['is_active' => false]);
            return back()->with('success', 'تم حفظ الإعدادات وإيقاف الطريقة تلقائيًا لأنها غير مكتملة: '.implode(' ', $issues));
        }

        return back()->with('success', 'تم تحديث طريقة الدفع.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        if (! $paymentMethod->is_active) {
            $issues = $paymentMethod->activationIssues();
            if ($issues) {
                return back()->withErrors([
                    'payment_method' => 'لا يمكن تفعيل '.$paymentMethod->name.': '.implode(' ', $issues),
                ]);
            }
        }

        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return back()->with('success', $paymentMethod->fresh()->is_active
            ? 'تم تفعيل طريقة الدفع وستظهر للعملاء عندما يناسب المبلغ وحالة الطلب.'
            : 'تم إيقاف طريقة الدفع ولن تظهر للعملاء.');
    }

    public function installLibyaCatalog(LibyaPaymentMethodCatalog $catalog)
    {
        $result = $catalog->sync();

        return back()->with(
            'success',
            "تم تحديث دليل الدفع الليبي: {$result['created']} جديدة، {$result['updated']} محدثة، {$result['disabled']} أوقفت تلقائيًا لعدم اكتمال إعدادها، والإجمالي {$result['total']}. بيانات التاجر الموجودة لم تُمسح."
        );
    }

    private function validated(Request $request, ?PaymentMethod $method = null): array
    {
        return $request->validate([
            'code' => ['required','alpha_dash','max:80',Rule::unique('payment_methods','code')->ignore($method?->id)],
            'name' => ['required','string','max:150'],
            'type' => ['required', Rule::in(['manual','bank','wallet','api','cash'])],
            'sort_order' => ['required','integer','min:0','max:9999'],
            'min_amount' => ['nullable','numeric','min:0'],
            'max_amount' => ['nullable','numeric','min:0','gte:min_amount'],
            'fee_type' => ['required', Rule::in(['none','percentage','fixed'])],
            'fee_value' => ['required','numeric','min:0'],
            'instructions' => ['nullable','string','max:3000'],
            'config.integration_mode' => ['nullable', Rule::in(['manual_verification','merchant_qr','external_link','in_person','cash','partner_api'])],
            'config.proof_mode' => ['nullable', Rule::in(['none','reference','receipt','reference_or_receipt'])],
            'config.currency' => ['nullable','string','max:10'],
            'config.availability' => ['nullable', Rule::in(['online','delivery_only','all'])],
            'config.test_mode' => ['nullable', Rule::in(['0','1',0,1])],
            'config.provider_name' => ['nullable','string','max:190'],
            'config.official_activity' => ['nullable','string','max:500'],
            'config.documentation_status' => ['nullable','string','max:100'],
            'config.official_source' => ['nullable','string','max:1000'],
            'config.bank_name' => ['nullable','string','max:190'],
            'config.branch_name' => ['nullable','string','max:190'],
            'config.account_name' => ['nullable','string','max:190'],
            'config.iban' => ['nullable','string','max:100'],
            'config.account_number' => ['nullable','string','max:100'],
            'config.beneficiary_account' => ['nullable','string','max:190'],
            'config.wallet_number' => ['nullable','string','max:100'],
            'config.merchant_name' => ['nullable','string','max:190'],
            'config.phone' => ['nullable','string','max:50'],
            'config.qr_value' => ['nullable','string','max:4000'],
            'config.qr_image_url' => ['nullable','url','max:1000'],
            'config.merchant_account_number' => ['nullable','string','max:190'],
            'config.mcc' => ['nullable','string','max:20'],
            'config.city' => ['nullable','string','max:190'],
            'config.internal_reference_prefix' => ['nullable','string','max:100'],
            'config.merchant_id' => ['nullable','string','max:255'],
            'config.terminal_id' => ['nullable','string','max:255'],
            'config.acquirer_name' => ['nullable','string','max:190'],
            'config.merchant_contract_reference' => ['nullable','string','max:255'],
            'config.location' => ['nullable','string','max:255'],
            'config.api_key' => ['nullable','string','max:2000'],
            'config.secret_key' => ['nullable','string','max:2000'],
            'config.username' => ['nullable','string','max:255'],
            'config.password' => ['nullable','string','max:2000'],
            'config.webhook_secret' => ['nullable','string','max:2000'],
            'config.api_base_url' => ['nullable','url','max:1000'],
            'config.checkout_url' => ['nullable','url','max:1000'],
            'config.callback_url' => ['nullable','url','max:1000'],
            'config.return_url' => ['nullable','url','max:1000'],
            'config.cancel_url' => ['nullable','url','max:1000'],
        ]);
    }

    private function config(Request $request, ?PaymentMethod $method = null): array
    {
        $out = $method?->config ?? [];
        $normalKeys = [
            'provider_name','official_activity','documentation_status','official_source','integration_mode','proof_mode','currency','availability','test_mode',
            'bank_name','branch_name','account_name','iban','account_number','beneficiary_account','wallet_number','merchant_name','phone','qr_value','qr_image_url',
            'merchant_account_number','mcc','city','internal_reference_prefix','merchant_id','terminal_id','acquirer_name','merchant_contract_reference','location',
            'api_base_url','checkout_url','callback_url','return_url','cancel_url',
        ];
        $secretKeys = ['api_key','secret_key','username','password','webhook_secret'];

        foreach ($normalKeys as $key) {
            if ($request->has('config.'.$key)) {
                $out[$key] = $request->input('config.'.$key);
            }
        }

        foreach ($secretKeys as $key) {
            if ($request->filled('config.'.$key)) {
                $out[$key] = $request->input('config.'.$key);
            }
        }

        return $out;
    }
}
