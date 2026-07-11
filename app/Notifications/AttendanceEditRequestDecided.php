<?php

namespace App\Notifications;

use App\Channels\DatabaseChannel;
use App\Enums\ApprovalStatus;
use App\Models\AttendanceEditRequest;
use Illuminate\Notifications\Notification;

class AttendanceEditRequestDecided extends Notification
{
    public function __construct(private readonly AttendanceEditRequest $request) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $decision = $this->request->status === ApprovalStatus::Approved ? 'approved' : 'rejected';
        $record = $this->request->attendanceRecord;
        $student = $record->student;

        return [
            'type' => 'attendance_edit_request_decided',
            'title' => "Attendance edit request {$decision}",
            'message' => "Your edit request for {$student->first_name} {$student->last_name}'s attendance on {$record->date->format('M j, Y')} has been {$decision}.",
            'data' => ['attendance_edit_request_id' => $this->request->id, 'status' => $decision],
        ];
    }
}
