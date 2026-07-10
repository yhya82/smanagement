<?php

namespace App\Channels;

use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;

/**
 * Stands in for Laravel's built-in "database" channel, which assumes its
 * own polymorphic table shape (uuid id, notifiable_type/id, a single data
 * blob). Ours is the simpler user_id-FK table from the schema review -
 * this channel just adapts a Notification's toDatabase() output to that
 * shape. Every notification class routes here explicitly via its own
 * via(): [DatabaseChannel::class].
 */
class DatabaseChannel
{
    public function send(object $notifiable, Notification $notification): NotificationModel
    {
        $payload = $notification->toDatabase($notifiable);

        return $notifiable->notifications()->create([
            'type' => $payload['type'] ?? $notification::class,
            'title' => $payload['title'],
            'message' => $payload['message'],
            'data' => $payload['data'] ?? null,
        ]);
    }
}
