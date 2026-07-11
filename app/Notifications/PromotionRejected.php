<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\Promotion;
use Illuminate\Notifications\Notification;

/**
 * Kept separate from StudentPromoted rather than generalizing that class
 * to handle both outcomes - "StudentPromoted" firing for a rejection would
 * be a contradiction in terms, and StudentPromoted is already relied on
 * elsewhere with that exact meaning.
 */
class PromotionRejected extends Notification
{
    public function __construct(private readonly Promotion $promotion) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'promotion_rejected',
            'title' => 'Promotion not approved',
            'message' => "Your promotion to {$this->promotion->toClass->name} was not approved.",
            'data' => ['promotion_id' => $this->promotion->id],
        ];
    }
}
