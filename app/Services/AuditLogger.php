<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(string $eventType, string $title, ?User $actor = null, ?Model $subject = null, ?string $description = null, array $metadata = [], ?Order $order = null): AuditLog
    {
        if ($subject instanceof Order && $order === null) {
            $order = $subject;
        }

        $request = app()->bound('request') ? request() : null;

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'order_id' => $order?->id,
            'event_type' => $eventType,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
