<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: kolom generated `id_number_normalized` mengubah id_number kosong/NULL
 * menjadi string kosong '' (lewat COALESCE(...,'')), sehingga UNIQUE KEY
 * `unique_customer_idnumber` menolak customer kedua yang belum punya KTP
 * (dua-duanya dianggap '' = duplikat).
 *
 * Sejak revisi KTP dipindah ke form Penyewaan, HAMPIR SEMUA customer baru
 * dibuat tanpa id_number dulu — jadi bug ini langsung kena di customer ke-2
 * dan seterusnya.
 *
 * Perbaikan: bungkus dengan NULLIF(...,'') supaya hasil kosong jadi NULL
 * asli. MySQL mengizinkan banyak NULL pada kolom UNIQUE (NULL != NULL secara
 * definisi SQL), sedangkan '' == '' tetap dianggap duplikat seperti biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Index harus dilepas dulu sebelum kolom yang direferensikannya diubah.
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('unique_customer_idnumber');
        });

        DB::statement("
            ALTER TABLE `customers`
            MODIFY COLUMN `id_number_normalized` varchar(100)
            COLLATE utf8mb4_unicode_ci
            GENERATED ALWAYS AS (
                NULLIF(REGEXP_REPLACE(COALESCE(`id_number`, ''), '[^0-9A-Za-z]', ''), '')
            ) STORED
        ");

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('id_number_normalized', 'unique_customer_idnumber');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('unique_customer_idnumber');
        });

        DB::statement("
            ALTER TABLE `customers`
            MODIFY COLUMN `id_number_normalized` varchar(100)
            COLLATE utf8mb4_unicode_ci
            GENERATED ALWAYS AS (
                REGEXP_REPLACE(COALESCE(`id_number`, ''), '[^0-9A-Za-z]', '')
            ) STORED
        ");

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('id_number_normalized', 'unique_customer_idnumber');
        });
    }
};
