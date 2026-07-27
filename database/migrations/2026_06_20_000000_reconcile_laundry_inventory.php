<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Laundry;
use App\Models\Rental;

/**
 * DATA RECONCILIATION MIGRATION
 *
 * Tujuan: Memperbaiki data lama yang sudah terlanjur tersimpan dengan status
 * yang tidak konsisten akibat bug pada LaundryController.
 *
 * Kasus yang diperbaiki:
 *  1. laundries.status = 'siap_disewakan' tapi products.stock_available = 0
 *     → increment stock dan set status produk ke 'available'
 *
 *  2. laundries.status = 'siap_disewakan' tapi rentals.rental_status = 'returned'
 *     → update rental_status ke 'siap_disewakan' agar konsisten
 *
 * Jalankan dengan: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {

        $siapDisewakan = DB::table('laundries')
            ->where('status', 'siap_disewakan')
            ->whereNull('deleted_at')
            ->get();

        foreach ($siapDisewakan as $laundry) {
            $product = DB::table('products')
                ->where('id', $laundry->produk_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$product) {
                continue;
            }

            // Hitung berapa item 'siap_disewakan' untuk produk ini
            $siapCount = DB::table('laundries')
                ->where('produk_id', $laundry->produk_id)
                ->where('status', 'siap_disewakan')
                ->whereNull('deleted_at')
                ->count();

            // Hitung berapa item yang masih aktif disewa (belum kembali)
            $rentedCount = DB::table('laundries')
                ->join('rentals', 'laundries.transaksi_id', '=', 'rentals.id')
                ->where('laundries.produk_id', $laundry->produk_id)
                ->whereIn('rentals.rental_status', ['active', 'overdue', 'menunggu_laundry', 'dalam_laundry'])
                ->whereNull('laundries.deleted_at')
                ->count();

            // stock_available = stock_total - jumlah yang masih disewa
            $expectedAvailable = $product->stock_total - $rentedCount;
            $expectedAvailable = max(0, $expectedAvailable); // tidak boleh negatif

            if ($product->stock_available != $expectedAvailable) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'stock_available' => $expectedAvailable,
                        'status'          => $expectedAvailable > 0 ? 'available' : 'rented',
                        'updated_at'      => now(),
                    ]);
            }
        }

        // ── Fix 2: rental_status yang terlanjur di-set 'returned' ──────────
        //
        // Cari rental yang laundry-nya sudah 'siap_disewakan' tapi
        // rental_status-nya masih 'returned' (akibat bug lama).

        DB::table('rentals')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('laundries')
                    ->whereColumn('laundries.transaksi_id', 'rentals.id')
                    ->where('laundries.status', 'siap_disewakan')
                    ->whereNull('laundries.deleted_at');
            })
            ->where('rental_status', 'returned')
            ->whereNull('deleted_at')
            ->update([
                'rental_status' => 'siap_disewakan',
                'updated_at'    => now(),
            ]);

        // ── Fix 3: rental_status yang salah di 'menunggu_laundry' padahal
        //    laundry sudah 'dalam_laundry' ────────────────────────────────────

        DB::table('rentals')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('laundries')
                    ->whereColumn('laundries.transaksi_id', 'rentals.id')
                    ->where('laundries.status', 'dalam_laundry')
                    ->whereNull('laundries.deleted_at');
            })
            ->where('rental_status', 'menunggu_laundry') // seharusnya sudah dalam_laundry
            ->whereNull('deleted_at')
            ->update([
                'rental_status' => 'dalam_laundry',
                'updated_at'    => now(),
            ]);
    }

    public function down(): void
    {
        // Rollback tidak dimungkinkan tanpa snapshot data lama.
        // Tidak melakukan apa-apa di sini adalah aman.
    }
};
