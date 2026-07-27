<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOrphanRentalData extends Command
{
    protected $signature = 'app:clean-orphan-rentals {--dry-run : Hanya tampilkan tanpa menghapus}';

    protected $description = 'Cek dan bersihkan rental (beserta data turunannya) yang customer_id-nya sudah tidak ada di tabel customers';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $existingCustomerIds = DB::table('customers')->pluck('id');

        $orphanRentalIds = DB::table('rentals')
            ->whereNotIn('customer_id', $existingCustomerIds)
            ->pluck('id');

        $orphanBroadcastLogIds = DB::table('broadcast_logs')
            ->whereNotIn('customer_id', $existingCustomerIds)
            ->pluck('id');

        if ($orphanRentalIds->isEmpty() && $orphanBroadcastLogIds->isEmpty()) {
            $this->info('✔ Tidak ada orphan data. Database bersih.');
            return self::SUCCESS;
        }

        if ($orphanRentalIds->isNotEmpty()) {
            $this->warn("Ditemukan {$orphanRentalIds->count()} rental orphan: " . $orphanRentalIds->implode(', '));
        }
        if ($orphanBroadcastLogIds->isNotEmpty()) {
            $this->warn("Ditemukan {$orphanBroadcastLogIds->count()} broadcast_log orphan: " . $orphanBroadcastLogIds->implode(', '));
        }

        if ($dryRun) {
            $this->comment('Dry run — tidak ada data yang dihapus. Jalankan tanpa --dry-run untuk membersihkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($orphanRentalIds, $orphanBroadcastLogIds) {
            if ($orphanRentalIds->isNotEmpty()) {
                DB::table('laundries')->whereIn('transaksi_id', $orphanRentalIds)->delete();
                DB::table('rental_returns')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('guarantees')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('payments')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('rental_items')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('rentals')->whereIn('id', $orphanRentalIds)->delete();
            }

            if ($orphanBroadcastLogIds->isNotEmpty()) {
                DB::table('broadcast_logs')->whereIn('id', $orphanBroadcastLogIds)->delete();
            }
        });

        $this->info('✔ Orphan data berhasil dibersihkan.');
        return self::SUCCESS;
    }
}
