<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notification delivery is a side effect of a business action, never part
 * of it - a failure writing a notification must not roll back an approval
 * that already happened (when called inside a transaction) or surface as
 * if the action itself failed (when called after one). Every service that
 * notifies someone after an approve/reject/create should go through this
 * rather than calling ->notify() directly.
 */
class SafeNotifier
{
    public static function send(mixed $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (Throwable $e) {
            Log::error('Notification delivery failed', [
                'notifiable_type' => is_object($notifiable) ? get_class($notifiable) : gettype($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => get_class($notification),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
