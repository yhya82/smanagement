<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\StudentApplication;
use Illuminate\Notifications\Notification;

class ApplicationSubmitted extends Notification
{
    public function __construct(private readonly StudentApplication $application) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'application_submitted',
            'title' => 'New application submitted',
            'message' => "{$this->application->first_name} {$this->application->last_name}'s application is awaiting review.",
            'data' => ['application_id' => $this->application->id],
        ];
    }
}
