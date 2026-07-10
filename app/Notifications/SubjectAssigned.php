<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Notifications\Notification;

class SubjectAssigned extends Notification
{
    public function __construct(private readonly TeacherSubjectAssignment $assignment) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $subjectName = $this->assignment->subject->name;
        $className = $this->assignment->schoolClass->name;

        return [
            'type' => 'subject_assigned',
            'title' => 'New subject assignment',
            'message' => "You've been assigned to teach {$subjectName} for {$className}.",
            'data' => ['assignment_id' => $this->assignment->id],
        ];
    }
}
