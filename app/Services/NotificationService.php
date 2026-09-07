<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(private readonly CustomerOrderEmailNotifier $customerEmail) {}

    public function notifyUser(User $user, string $type, string $title, ?string $body = null, ?string $url = null, ?string $icon = null, array $data = []): AppNotification
    {
        return $user->appNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon,
            'data' => $data,
        ]);
    }

    public function notifyCustomer(Order $order, string $type, string $title, ?string $body = null, ?string $icon = null, array $data = []): ?AppNotification
    {
        $order->loadMissing('user');
        if (! $order->user) {
            return null;
        }

        $notification = $this->notifyUser(
            $order->user,
            $type,
            $title,
            $body,
            route('orders.show', $order),
            $icon,
            array_merge(['order_id' => $order->id, 'order_number' => $order->number], $data),
        );

        $this->customerEmail->send($order, $title, $body);

        return $notification;
    }

    public function notifyBackoffice(Order $order, string $type, string $title, ?string $body = null, ?string $icon = null, array $data = []): Collection
    {
        $ids = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($order) {
                $query->where('role', 'admin');
                if ($order->assigned_to) {
                    $query->orWhere('id', $order->assigned_to);
                } else {
                    $query->orWhere('role', 'order_manager');
                }
            })
            ->pluck('id')
            ->unique();

        return User::query()->whereIn('id', $ids)->get()->map(fn (User $user) => $this->notifyUser(
            $user,
            $type,
            $title,
            $body,
            route('admin.orders.show', $order),
            $icon,
            array_merge(['order_id' => $order->id, 'order_number' => $order->number], $data),
        ));
    }

    public function notifyAdmins(string $type, string $title, ?string $body = null, ?string $url = null, ?string $icon = null, array $data = []): Collection
    {
        return User::query()->where('role', 'admin')->where('is_active', true)->get()
            ->map(fn (User $user) => $this->notifyUser($user, $type, $title, $body, $url, $icon, $data));
    }
}
