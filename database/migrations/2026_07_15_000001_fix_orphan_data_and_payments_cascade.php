<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * STEP 1: Bersihkan orphan rental yang SUDAH terlanjur ada
     * (akibat bug guard forceDestroy() di CustomerController yang belum
     * memakai withTrashed() saat mengecek riwayat rental).
     *
     * STEP 2: payments.rental_id dulunya RESTRICT (tanpa onDelete),
     * tidak konsisten dengan rental_items/guarantees/rental_returns
     * yang sudah cascadeOnDelete(). Disamakan jadi CASCADE.
     *
     * STEP 3: rentals.customer_id dibuat EKSPLISIT restrictOnDelete()
     * — perilakunya sama seperti sebelumnya (default), tapi sekarang
     * dituliskan jelas di migration supaya niatnya terbaca: customer
     * TIDAK BOLEH dihapus permanen selama masih py rental (guard utama
     * tetap di level aplikasi/CustomerController, ini cuma jaring
     * pengaman kedua di level database).
     */
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────
        // STEP 1: CLEANUP DATA ORPHAN YANG SUDAH ADA
        // ─────────────────────────────────────────────────────────────
        DB::transaction(function () {
            $existingCustomerIds = DB::table('customers')->pluck('id');

            $orphanRentalIds = DB::table('rentals')
                ->whereNotIn('customer_id', $existingCustomerIds)
                ->pluck('id');

            if ($orphanRentalIds->isNotEmpty()) {
                DB::table('laundries')->whereIn('transaksi_id', $orphanRentalIds)->delete();
                DB::table('rental_returns')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('guarantees')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('payments')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('rental_items')->whereIn('rental_id', $orphanRentalIds)->delete();
                DB::table('rentals')->whereIn('id', $orphanRentalIds)->delete();
            }

            // broadcast_logs yang customer_id-nya sudah tidak ada
            DB::table('broadcast_logs')
                ->whereNotIn('customer_id', $existingCustomerIds)
                ->delete();

            // activity_logs SENGAJA TIDAK dibersihkan — audit trail global
            // (polymorphic, tanpa FK fisik), harus tetap ada sebagai bukti
            // histori meski entitas terkait sudah dihapus permanen.
        });

        // ─────────────────────────────────────────────────────────────
        // STEP 2: payments.rental_id → CASCADE (konsisten dgn sibling lain)
        // ─────────────────────────────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['rental_id']);
            $table->foreign('rental_id')
                  ->references('id')->on('rentals')
                  ->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────────────────────
        // STEP 3: rentals.customer_id → eksplisit RESTRICT (dokumentasi niat)
        // ─────────────────────────────────────────────────────────────
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['rental_id']);
            $table->foreign('rental_id')->references('id')->on('rentals');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers');
        });
    }
};
