<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'hadir';
    case Late = 'telat';
    case Excused = 'izin';
    case Absent = 'alpa';
}
