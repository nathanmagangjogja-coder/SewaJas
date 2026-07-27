<?php

namespace App\Console\Commands;

use App\Jobs\SendBroadcastJob;
use App\Models\BroadcastSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchBroadcasts extends Command
{
    protected $signature   = 'broadcast:dispatch';
    protected $description = 'Dispatch broadcast jobs sesuai jadwal waktu yang ditentukan';

    public function handle(): void
    {
        // ── PERBAIKAN UTAMA: bandingkan jam:menit secara string, bukan strtotime ──
        $now = Carbon::now('Asia/Jakarta');
        $currentHHMM = $now->format('H:i'); // "13:07"

        $this->info("Cek broadcast pada: {$currentHHMM}");
        Log::info('[Broadcast] DispatchBroadcasts jalan', ['time' => $currentHHMM]);

        $schedules = BroadcastSchedule::where('is_active', true)->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal aktif.');
            return;
        }

        foreach ($schedules as $schedule) {
            $times = $schedule->send_at_times ?? [];

            if (empty($times)) {
                Log::info("[Broadcast] Skip {$schedule->name}: tidak ada waktu terdaftar");
                continue;
            }

            // Cek apakah jam sekarang cocok dengan salah satu slot (±5 menit)
            $matched = false;
            $matchedSlot = null;

            foreach ($times as $slot) {
                if ($this->timeMatches($currentHHMM, $slot)) {
                    $matched    = true;
                    $matchedSlot = $slot;
                    break;
                }
            }

            if (!$matched) {
                Log::info("[Broadcast] Skip {$schedule->name}: waktu tidak cocok", [
                    'now'   => $currentHHMM,
                    'slots' => $times,
                ]);
                continue;
            }

            $this->info("✓ Matched: {$schedule->name} @ {$matchedSlot}");
            Log::info("[Broadcast] Match ditemukan: {$schedule->name} @ {$matchedSlot}");

            // Ambil pelanggan eligible
            $customers = $this->eligibleCustomers($schedule);

            if ($customers->isEmpty()) {
                Log::info("[Broadcast] Skip {$schedule->name}: tidak ada pelanggan eligible");
                continue;
            }

            $dispatched = 0;
            foreach ($customers as $customer) {
                // Cek cooldown: jangan kirim kalau sudah kirim dalam X jam terakhir
                $lastLog = $customer->broadcastLogs()
                    ->where('broadcast_schedule_id', $schedule->id)
                    ->where('status', 'sent')
                    ->latest('sent_at')
                    ->first();

                if ($lastLog) {
                    $hoursSince = Carbon::parse($lastLog->sent_at)->diffInHours($now);
                    if ($hoursSince < ($schedule->cooldown_hours ?? 24)) {
                        Log::info("[Broadcast] Skip customer {$customer->id}: cooldown belum habis ({$hoursSince}h)");
                        continue;
                    }
                }

                SendBroadcastJob::dispatch($customer->id, $schedule->id, 0);
                $dispatched++;
            }

            Log::info("[Broadcast] {$schedule->name}: {$dispatched} job di-dispatch");
            $this->info("  → {$dispatched} pesan di-dispatch");
        }
    }

    /**
     * FIX UTAMA: bandingkan jam:menit sebagai string dalam window ±5 menit.
     * Tidak pakai strtotime() karena akan membandingkan timestamp berbeda hari.
     */
    private function timeMatches(string $currentHHMM, string $slot): bool
    {
        // Validasi format HH:MM
        if (!preg_match('/^\d{2}:\d{2}$/', $slot)) return false;

        // Parse keduanya ke menit-dari-tengah-malam
        [$ch, $cm] = array_map('intval', explode(':', $currentHHMM));
        [$sh, $sm] = array_map('intval', explode(':', $slot));

        $currentMinutes = $ch * 60 + $cm;
        $slotMinutes    = $sh * 60 + $sm;

        // Window ±5 menit (command jalan setiap menit via cron)
        return abs($currentMinutes - $slotMinutes) <= 5;
    }

    private function eligibleCustomers(BroadcastSchedule $schedule)
    {
        $query = \App\Models\Customer::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_blacklisted', false);

        return match($schedule->target_audience) {
            'active_renters'  => $query->whereHas('rentals', fn($q) => $q->whereIn('rental_status', ['active', 'overdue']))->get(),
            'overdue'         => $query->whereHas('rentals', fn($q) => $q->where('rental_status', 'overdue'))->get(),
            'returning_soon'  => $query->whereHas('rentals', fn($q) => $q->whereIn('rental_status', ['active', 'overdue'])->whereDate('return_due_date', '<=', now('Asia/Jakarta')->addDays(3)))->get(),
            default           => $query->get(), // 'all'
        };
    }
}