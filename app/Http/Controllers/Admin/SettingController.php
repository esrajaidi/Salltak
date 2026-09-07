<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit()
    {
        $settings = SystemSetting::query()->pluck('value', 'key');
        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'home_intro' => ['nullable', 'string', 'max:1000'],
        ]);
        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->audit->log('settings.updated', 'تحديث إعدادات النظام', $request->user(), null, null, ['keys'=>array_keys($data)]);
        return back()->with('success', 'تم حفظ الإعدادات.');
    }
}
