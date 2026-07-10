<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

/**
 * Built to satisfy SRS §19's named notification list, but there is no
 * Timetable model/table anywhere in the finalized schema - "Timetable"
 * appears only as a label in §14's academic-structure hierarchy
 * (Year -> Term -> Grade -> Class -> Subject -> Teacher -> Timetable), never
 * modeled as its own entity. Nothing currently constructs or sends this
 * notification; wire it up once an actual timetable feature and its
 * underlying table exist, rather than inventing one here as a side effect
 * of Phase 10.
 */
class TimetableChanged extends Notification
{
    public function __construct(private readonly array $details) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'timetable_changed',
            'title' => 'Timetable changed',
            'message' => $this->details['message'] ?? 'Your timetable has changed.',
            'data' => $this->details,
        ];
    }
}
