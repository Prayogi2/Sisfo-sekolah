<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Graduated = 'graduated';
    case Inactive = 'inactive';
}
