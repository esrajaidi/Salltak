<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index() { return view('admin.exchange-rates.index', ['rates' => ExchangeRate::orderBy('currency')->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'currency' => ['required', 'alpha', 'size:3'],
            'rate_to_lyd' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);
        $rate = ExchangeRate::updateOrCreate(
            ['currency' => strtoupper($data['currency'])],
            ['rate_to_lyd' => $data['rate_to_lyd'], 'is_active' => true, 'updated_by' => $request->user()->id]
        );
        $this->audit->log('exchange_rate.saved', 'حفظ سعر صرف', $request->user(), $rate, $rate->currency.' = '.$rate->rate_to_lyd.' LYD');
        return back()->with('success', 'تم حفظ سعر الصرف.');
    }

    public function toggle(Request $request, ExchangeRate $exchangeRate)
    {
        $exchangeRate->update(['is_active' => ! $exchangeRate->is_active, 'updated_by' => $request->user()->id]);
        $this->audit->log('exchange_rate.toggled', $exchangeRate->is_active ? 'تفعيل سعر صرف' : 'إيقاف سعر صرف', $request->user(), $exchangeRate, $exchangeRate->currency);
        return back()->with('success', 'تم تحديث حالة سعر الصرف.');
    }
}
