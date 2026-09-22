<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:mark-absent')->dailyAt('07:30');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jaring pengaman: kuis yang waktunya habis tetap dikumpulkan & dinilai
// walau perangkat siswa mati atau kehilangan koneksi.
Schedule::command('kuis:auto-kumpulkan')->everyMinute()->withoutOverlapping();
