<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class StudentImportFailed extends Notification
{
    public function __construct(
        private readonly int $classId,
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'student_import_failed',
            'title' => 'Student import failed',
            'message' => 'Your student import failed outright and will not retry automatically. Please re-upload the CSV.',
            'data' => ['class_id' => $this->classId],
        ];
    }
}
