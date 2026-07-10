<?php

namespace App\Enums;

enum TeacherStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
}
