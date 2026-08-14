<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FITUR BARU: "Denda harus dibayar dulu sebelum barang bisa dikembalikan".
 *
 * Sebelumnya nominal `late_fee` diisi manual DI DALAM form pengembalian
 * fisik barang (satu form, satu submit) — akibatnya barang bisa langsung
 * ditandai "dikembalikan" walau tagihan denda belum dibayar sama sekali.
 *
 * Sekarang proses dipecah jadi 2 langkah:
 *   1. Staf menentukan nominal denda lebih dulu (bisa Rp 0 kalau memang
 *      tidak ada denda) lewat endpoint terpisah (rentals.late-fee.set).
 *   2. Form pengembalian fisik baru muncul/bisa disubmit setelah denda
 *      tsb LUNAS dibayar (payment_status kembali 'paid').
 *
 * `late_fee_confirmed_at` dipakai untuk membedakan dua kondisi yang kalau
 * hanya mengandalkan kolom `late_fee` akan sama-sama bernilai 0 dan tidak
 * bisa dibedakan:
 *   - "Denda belum ditentukan sama sekali" (late_fee = 0, belum diisi staf)
 *   - "Staf sudah menentukan: memang tidak ada denda" (late_fee = 0, sudah
 *     dikonfirmasi)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('late_fee_confirmed_at')->nullable()->after('late_fee_note');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('late_fee_confirmed_at');
        });
    }
};
