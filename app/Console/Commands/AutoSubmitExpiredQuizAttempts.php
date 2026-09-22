<?php

namespace App\Console\Commands;

use App\Services\QuizAttemptFinalizer;
use Illuminate\Console\Command;

class AutoSubmitExpiredQuizAttempts extends Command
{
    protected $signature = 'kuis:auto-kumpulkan';

    protected $description = 'Mengumpulkan otomatis kuis yang waktunya sudah habis tapi belum dikumpulkan siswa';

    public function handle(QuizAttemptFinalizer $finalizer): int
    {
        $finalized = $finalizer->finalizeExpired();

        $this->info($finalized === 0
            ? 'Tidak ada kuis kedaluwarsa yang perlu dikumpulkan.'
            : "{$finalized} kuis yang waktunya habis berhasil dikumpulkan otomatis.");

        return self::SUCCESS;
    }
}
