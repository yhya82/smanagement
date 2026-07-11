<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class RankingComputeFailed extends Notification
{
    public function __construct(
        private readonly int $termId,
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ranking_compute_failed',
            'title' => 'Ranking computation failed',
            'message' => 'The ranking computation you requested failed outright and will not retry automatically. Please try again.',
            'data' => ['term_id' => $this->termId],
        ];
    }
}
