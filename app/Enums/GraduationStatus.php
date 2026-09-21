<?php

namespace App\Enums;

enum GraduationStatus: string
{
    case NotYetGraduated = 'belum_lulus';
    case Graduated = 'lulus';
    case NotGraduated = 'tidak_lulus';
}
