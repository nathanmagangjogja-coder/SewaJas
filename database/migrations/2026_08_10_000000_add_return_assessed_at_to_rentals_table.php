<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sekarang proses retur dipecah jadi 2 fase:
 *   1. ASSESSMENT — staf mencatat kondisi tiap barang & menghitung denda
 *      rusak/hilang (kalau ada). Ditandai lewat `return_assessed_at`.
 *      Barang BELUM ditandai is_returned & rental_status BELUM maju.
 *   2. FINALISASI — hanya terjadi otomatis setelah tagihan (subtotal +
 *      denda telat + denda rusak/hilang - diskon) LUNAS dibayar. Baru di
 *      sini barang ditandai is_returned, masuk laundry, rental_status maju.
 *
 * Kalau saat assessment ternyata tidak ada kekurangan bayar sama sekali
 * (semua barang kondisi baik, atau kerusakan ditutup jaminan), fase 2
 * langsung berjalan otomatis di detik yang sama — staf tidak merasakan ada
 * langkah tambahan seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('return_assessed_at')->nullable()->after('late_fee_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('return_assessed_at');
        });
    }
};
