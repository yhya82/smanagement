<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\TimetableEntry;
use Illuminate\Notifications\Notification;

class TimetableChanged extends Notification
{
    public function __construct(private readonly TimetableEntry $entry) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $day = ucfirst($this->entry->day_of_week);

        return [
            'type' => 'timetable_changed',
            'title' => 'Timetable updated',
            'message' => "Your {$day} {$this->entry->period->name} slot for {$this->entry->schoolClass->name} is now {$this->entry->subject->name}.",
            'data' => ['timetable_entry_id' => $this->entry->id],
        ];
    }
}
