<?php

namespace App\Console\Commands;

use App\Models\Rental;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanExpiredFiles extends Command
{
    protected $signature   = 'storage:clean-expired {--days=90}';
    protected $description = 'Hapus QR code dan file jaminan yang sudah tidak dipakai';

    public function handle(): void
    {
        $days = (int) $this->option('days');
        $this->info("Membersihkan file rental selesai > {$days} hari lalu...");

        // 1. Hapus QR code rental yang sudah selesai / cancelled
        $expired = Rental::whereIn('rental_status', ['siap_disewakan', 'cancelled', 'returned'])
            ->where('updated_at', '<', now()->subDays($days))
            ->whereNotNull('qr_code')
            ->get();

        $deletedQr = 0;
        foreach ($expired as $rental) {
            if ($rental->qr_code && Storage::disk('public')->exists($rental->qr_code)) {
                Storage::disk('public')->delete($rental->qr_code);
                $rental->update(['qr_code' => null]);
                $deletedQr++;
            }
        }
        $this->info("  ✓ {$deletedQr} QR code dihapus.");

        // 2. Hapus foto jaminan orphan (tidak terhubung ke record apapun)
        $existingPhotos = \App\Models\Guarantee::whereNotNull('photo')
            ->pluck('photo')->toArray();
        $storedFiles  = Storage::disk('public')->files('guarantees');
        $orphaned     = 0;

        foreach ($storedFiles as $file) {
            $rel = str_replace('public/', '', $file);
            if (!in_array($rel, $existingPhotos)) {
                Storage::disk('public')->delete($file);
                $orphaned++;
            }
        }
        $this->info("  ✓ {$orphaned} file jaminan orphan dihapus.");

        Log::info("storage:clean-expired — QR: {$deletedQr}, orphan: {$orphaned}");
    }
}
