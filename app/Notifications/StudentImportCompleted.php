<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\SchoolClass;
use Illuminate\Notifications\Notification;

class StudentImportCompleted extends Notification
{
    public function __construct(
        private readonly SchoolClass $class,
        private readonly int $created,
        private readonly int $failed,
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Import for {$this->class->name} complete: {$this->created} created";
        $message .= $this->failed > 0 ? ", {$this->failed} failed." : '.';

        return [
            'type' => 'student_import_completed',
            'title' => 'Student import complete',
            'message' => $message,
            'data' => ['class_id' => $this->class->id, 'created' => $this->created, 'failed' => $this->failed],
        ];
    }
}
