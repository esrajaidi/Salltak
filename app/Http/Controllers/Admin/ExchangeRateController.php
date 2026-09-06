<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index() { return view('admin.exchange-rates.index', ['rates' => ExchangeRate::orderBy('currency')->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'currency' => ['required', 'alpha', 'size:3'],
            'rate_to_lyd' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);
        ExchangeRate::updateOrCreate(
            ['currency' => strtoupper($data['currency'])],
            ['rate_to_lyd' => $data['rate_to_lyd'], 'is_active' => true, 'updated_by' => $request->user()->id]
        );
        return back()->with('success', 'تم حفظ سعر الصرف.');
    }

    public function toggle(Request $request, ExchangeRate $exchangeRate)
    {
        $exchangeRate->update(['is_active' => ! $exchangeRate->is_active, 'updated_by' => $request->user()->id]);
        return back()->with('success', 'تم تحديث حالة سعر الصرف.');
    }
}
