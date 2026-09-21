<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;

readonly class AttendanceScanResult
{
    /**
     * @param  'check_in'|'check_out'|'duplicate'  $event
     */
    public function __construct(
        public Student $student,
        public Attendance $attendance,
        public string $event,
    ) {}
}
