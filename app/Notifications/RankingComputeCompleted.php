<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\Term;
use Illuminate\Notifications\Notification;

class RankingComputeCompleted extends Notification
{
    public function __construct(
        private readonly Term $term,
        private readonly int $classCount,
        private readonly int $rankingCount,
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ranking_compute_completed',
            'title' => 'Rankings computed',
            'message' => "Computed {$this->rankingCount} ranking(s) across {$this->classCount} class(es) for '{$this->term->name}'.",
            'data' => ['term_id' => $this->term->id, 'class_count' => $this->classCount, 'ranking_count' => $this->rankingCount],
        ];
    }
}
