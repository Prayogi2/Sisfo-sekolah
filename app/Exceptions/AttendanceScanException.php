<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kegagalan yang diharapkan (bukan bug) saat memproses scan QR presensi,
 * mis. token tidak dikenali atau siswa belum punya kelas/jadwal.
 */
class AttendanceScanException extends RuntimeException {}
