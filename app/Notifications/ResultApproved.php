<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\ResultEntry;
use Illuminate\Notifications\Notification;

class ResultApproved extends Notification
{
    public function __construct(private readonly ResultEntry $result) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'result_approved',
            'title' => 'Result approved',
            'message' => "Your result for {$this->result->subject->name} has been approved.",
            'data' => ['result_entry_id' => $this->result->id],
        ];
    }
}
