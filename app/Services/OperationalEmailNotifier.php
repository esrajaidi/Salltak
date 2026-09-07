<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OperationalEmailNotifier
{
    public function send(string $event, string $subject, string $body, ?string $url = null): void
    {
        if (! $this->enabled($event)) {
            return;
        }

        $recipients = $this->recipients();
        if ($recipients === []) {
            return;
        }

        $platform = (string) SystemSetting::getValue('platform_name', 'سلتك');
        $html = view('emails.operational-alert', [
            'platform' => $platform,
            'subject' => $subject,
            'body' => $body,
            'url' => $url,
        ])->render();

        foreach ($recipients as $recipient) {
            try {
                Mail::html($html, function ($message) use ($recipient, $subject, $platform) {
                    $message->to($recipient)->subject($platform.' - '.$subject);
                });
            } catch (Throwable $e) {
                Log::warning('تعذر إرسال إشعار تشغيلي بالبريد.', [
                    'event' => $event,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return array<int,string> */
    public function recipients(): array
    {
        $raw = (string) SystemSetting::getValue('notification_emails', '');
        $parts = preg_split('/[\s,;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $valid = array_filter(array_map('trim', $parts), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        return array_values(array_unique(array_map('strtolower', $valid)));
    }

    private function enabled(string $event): bool
    {
        $key = match ($event) {
            'new_order' => 'notify_email_new_order',
            'new_message' => 'notify_email_new_message',
            'payment' => 'notify_email_payment',
            default => null,
        };

        if ($key === null) {
            return false;
        }

        return filter_var(SystemSetting::getValue($key, '1'), FILTER_VALIDATE_BOOL);
    }
}
