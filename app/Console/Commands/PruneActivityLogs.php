<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

/**
 * Log aktivitas tumbuh terus; perintah ini membuang jejak yang sudah terlalu
 * lama supaya tabelnya tidak membebani hosting. Dijadwalkan bulanan di
 * routes/console.php.
 */
class PruneActivityLogs extends Command
{
    protected $signature = 'activity:prune {--days=365 : Simpan log sebanyak hari terakhir ini}';

    protected $description = 'Menghapus log aktivitas yang lebih lama dari jumlah hari yang ditentukan';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Date::now()->subDays($days)->startOfDay();

        $deleted = ActivityLog::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("{$deleted} log aktivitas sebelum {$cutoff->toDateString()} dihapus.");

        return self::SUCCESS;
    }
}
