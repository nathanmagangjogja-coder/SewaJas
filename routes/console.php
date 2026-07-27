<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════
// SCHEDULED TASKS
// Aktif setelah cron dipasang di server:
//   * * * * * cd /path/project && php artisan schedule:run
// ═══════════════════════════════════════════════════════

// Tandai rental yang melewati jatuh tempo → status 'overdue'
Schedule::command('rentals:update-overdue')
    ->dailyAt('00:01')
    ->withoutOverlapping()
    ->runInBackground();

// Kirim WA reminder H-1 jatuh tempo ke customer
Schedule::command('rentals:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Broadcast WhatsApp otomatis setiap lima menit untuk jadwal aktif
Schedule::command('broadcast:dispatch')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Bersihkan file QR dan jaminan lama (>90 hari) setiap Minggu
Schedule::command('storage:clean-expired')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping();