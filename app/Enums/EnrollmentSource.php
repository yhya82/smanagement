<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Individual = 'individual';
    case Import = 'import';
}
