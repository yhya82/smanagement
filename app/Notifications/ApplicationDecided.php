<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Enums\ApprovalStatus;
use App\Models\StudentApplication;
use Illuminate\Notifications\Notification;

class ApplicationDecided extends Notification
{
    public function __construct(private readonly StudentApplication $application) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $decision = $this->application->status === ApprovalStatus::Approved ? 'approved' : 'rejected';

        return [
            'type' => 'application_decided',
            'title' => "Application {$decision}",
            'message' => "{$this->application->first_name} {$this->application->last_name}'s application has been {$decision}.",
            'data' => ['application_id' => $this->application->id, 'status' => $decision],
        ];
    }
}
