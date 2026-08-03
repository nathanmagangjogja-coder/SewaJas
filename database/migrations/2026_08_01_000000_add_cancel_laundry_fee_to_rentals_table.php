<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan biaya laundry yang dikenakan saat penyewaan DIBATALKAN
     * pada masa AKTIF (barang sudah dipegang/dipakai customer, bukan lagi
     * sekadar dipesan). Dipisah dari `late_fee` karena konteksnya beda:
     * late_fee = denda keterlambatan pengembalian, cancel_laundry_fee =
     * biaya laundry akibat pembatalan dini saat barang sudah keluar toko.
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('cancel_laundry_fee', 12, 2)->default(0)->after('late_fee_note');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('cancel_laundry_fee');
        });
    }
};
