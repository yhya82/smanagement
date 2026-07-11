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
        private readonly int $failedCount = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Computed {$this->rankingCount} ranking(s) across {$this->classCount} class(es) for '{$this->term->name}'.";

        if ($this->failedCount > 0) {
            $message .= " {$this->failedCount} class(es) failed and may need re-running.";
        }

        return [
            'type' => 'ranking_compute_completed',
            'title' => 'Rankings computed',
            'message' => $message,
            'data' => [
                'term_id' => $this->term->id,
                'class_count' => $this->classCount,
                'ranking_count' => $this->rankingCount,
                'failed_count' => $this->failedCount,
            ],
        ];
    }
}
