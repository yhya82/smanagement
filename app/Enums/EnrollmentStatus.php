<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Transferred = 'transferred';
    case Withdrawn = 'withdrawn';
}
