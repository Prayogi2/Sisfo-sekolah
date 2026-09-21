<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Str;

class StudentObserver
{
    /**
     * Beri token QR kartu digital yang aman (bukan format tebakan)
     * sebelum siswa disimpan pertama kali.
     */
    public function creating(Student $student): void
    {
        $student->qr_token ??= Str::random(40);
    }
}
