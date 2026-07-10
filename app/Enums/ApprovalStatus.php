<?php

namespace App\Enums;

/**
 * Shared by every table whose lifecycle is just pending/approved/rejected:
 * student_applications, attendance_edit_requests, promotions.
 */
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
