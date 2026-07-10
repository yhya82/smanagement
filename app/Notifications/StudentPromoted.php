<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\Promotion;
use Illuminate\Notifications\Notification;

class StudentPromoted extends Notification
{
    public function __construct(private readonly Promotion $promotion) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'student_promoted',
            'title' => 'Promotion approved',
            'message' => "You have been promoted to {$this->promotion->toClass->name}.",
            'data' => ['promotion_id' => $this->promotion->id],
        ];
    }
}
