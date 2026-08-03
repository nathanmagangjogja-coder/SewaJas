<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai sampai kapan foto KTP/jaminan tersimpan (`customers.id_photo`)
     * masih boleh dipakai ulang otomatis ("generate KTP otomatis") tanpa
     * scan/upload ulang.
     *
     * null (default)           -> tidak dibatasi, selalu boleh dipakai ulang
     *                              (kondisi normal, belum pernah ada pembatalan).
     * timestamp di masa depan  -> boleh dipakai ulang SAMPAI waktu tsb saja.
     * timestamp di masa lalu   -> sudah kadaluarsa, wajib scan/upload ulang.
     *
     * Diisi otomatis oleh RentalService::cancelRental() saat sebuah rental
     * dibatalkan sebelum aktif:
     *  - customer masih punya sewa aktif lain -> diberi 30 menit.
     *  - tidak ada sewa aktif lain            -> langsung kadaluarsa.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('id_photo_reusable_until')->nullable()->after('id_photo_type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('id_photo_reusable_until');
        });
    }
};
