<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom metadata Diskon Manual (khusus alur retur/pengembalian).
     *
     * CATATAN: kolom `discount` (decimal) SUDAH ADA di tabel rentals dan
     * dipakai fitur diskon generik yang sudah ada (RentalController::updateDiscount()).
     * Migration ini TIDAK menyentuh/membuat ulang kolom itu — hanya menambah
     * 4 kolom metadata baru untuk mencatat detail diskon manual yang diinput
     * saat proses pengembalian barang (nama, deskripsi, tipe, nilai mentah).
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('rentals', 'discount_name')) {
                $table->string('discount_name')->nullable()->after('discount');
            }
            if (!Schema::hasColumn('rentals', 'discount_description')) {
                $table->text('discount_description')->nullable()->after('discount_name');
            }
            if (!Schema::hasColumn('rentals', 'discount_type')) {
                $table->enum('discount_type', ['nominal', 'percent'])->nullable()->after('discount_description');
            }
            if (!Schema::hasColumn('rentals', 'discount_value')) {
                $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $columns = ['discount_name', 'discount_description', 'discount_type', 'discount_value'];
            $existing = array_filter($columns, fn($col) => Schema::hasColumn('rentals', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
