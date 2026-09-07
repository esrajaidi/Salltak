<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'notification_emails' => ['nullable', 'string', 'max:4000'],
        ]);

        $emails = preg_split('/[\s,;]+/u', (string) ($data['notification_emails'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $invalid = array_values(array_filter($emails, fn ($email) => ! filter_var(trim($email), FILTER_VALIDATE_EMAIL)));
        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'notification_emails' => 'يوجد بريد غير صالح: '.implode('، ', $invalid),
            ]);
        }
        $data['notification_emails'] = implode(',', array_values(array_unique(array_map(fn ($email) => strtolower(trim($email)), $emails))));
        $data['notify_email_new_order'] = $request->boolean('notify_email_new_order') ? '1' : '0';
        $data['notify_email_new_message'] = $request->boolean('notify_email_new_message') ? '1' : '0';
        $data['notify_email_payment'] = $request->boolean('notify_email_payment') ? '1' : '0';
        $data['notify_email_customer_updates'] = $request->boolean('notify_email_customer_updates') ? '1' : '0';

        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->audit->log('settings.updated', 'تحديث إعدادات النظام', $request->user(), null, null, ['keys'=>array_keys($data)]);
        return back()->with('success', 'تم حفظ الإعدادات.');
    }
}
