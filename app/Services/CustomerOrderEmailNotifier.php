<?php

namespace App\Services;

use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CustomerOrderEmailNotifier
{
    public function send(Order $order, string $subject, ?string $body = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $order->loadMissing('user');
        $recipient = trim((string) $order->user?->email);
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $platform = (string) SystemSetting::getValue('platform_name', 'سلتك');
        $url = route('orders.show', $order);

        try {
            Mail::to($recipient)->send(new OrderUpdateMail(
                $platform,
                $subject,
                $body ?: 'يوجد تحديث جديد على طلبك.',
                $url,
            ));
        } catch (Throwable $e) {
            Log::warning('تعذر إرسال تحديث الطلب للعميل بالبريد.', [
                'order_id' => $order->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function enabled(): bool
    {
        return filter_var(SystemSetting::getValue('notify_email_customer_updates', '1'), FILTER_VALIDATE_BOOL);
    }
}
