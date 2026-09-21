<?php

namespace App\Enums;

enum EducationLevel: string
{
    case None = 'tidak_sekolah';
    case ElementarySchool = 'sd';
    case JuniorHighSchool = 'smp';
    case SeniorHighSchool = 'sma';
    case Diploma = 'diploma';
    case Bachelor = 's1';
    case Master = 's2';
    case Doctorate = 's3';
}
