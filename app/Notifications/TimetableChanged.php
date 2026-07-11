<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\TimetableEntry;
use Illuminate\Notifications\Notification;

class TimetableChanged extends Notification
{
    public function __construct(private readonly TimetableEntry $entry, private readonly bool $cleared = false) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $day = ucfirst($this->entry->day_of_week);
        $slot = "{$day} {$this->entry->period->name} slot for {$this->entry->schoolClass->name}";

        $message = $this->cleared
            ? "Your {$slot} has been cleared."
            : "Your {$slot} is now {$this->entry->subject->name}.";

        return [
            'type' => 'timetable_changed',
            'title' => 'Timetable updated',
            'message' => $message,
            'data' => ['timetable_entry_id' => $this->entry->id],
        ];
    }
}
