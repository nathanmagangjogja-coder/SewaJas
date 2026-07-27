<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Tabel paket sewa ────────────────────────────────────────────────
        Schema::create('rental_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Paket 1 Hari", "Paket 3 Hari", dst.
            $table->integer('duration_days');                // durasi sewa dalam hari
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // ── Denda keterlambatan ───────────────────────────────────────────
            // Denda = persentase dari harga paket item × hari terlambat
            // Contoh: penalty_percent = 10 → tiap hari telat = 10% dari harga paket
            $table->decimal('penalty_percent', 5, 2)->default(10.00)
                  ->comment('% dari subtotal per hari terlambat');

            // Cap maksimum denda (null = tidak ada batas)
            $table->decimal('max_penalty_percent', 5, 2)->nullable()
                  ->comment('Maksimum total denda (% dari subtotal). Null = tidak terbatas.');

            $table->timestamps();
        });

        // ── 2. Tambah kolom package_id ke rentals ─────────────────────────────
        Schema::table('rentals', function (Blueprint $table) {
            $table->foreignId('package_id')
                  ->nullable()
                  ->after('duration_days')
                  ->constrained('rental_packages')
                  ->nullOnDelete();
        });

        // ── 3. Tambah kolom package_id ke rental_items ────────────────────────
        Schema::table('rental_items', function (Blueprint $table) {
            $table->integer('package_duration_days')->nullable()->after('duration_days')
                  ->comment('Snapshot durasi paket saat transaksi dibuat');
        });

        // ── 4. Seed paket default ─────────────────────────────────────────────
        $now = now();
        DB::table('rental_packages')->insert([
            [
                'name'                => 'Paket 1 Hari',
                'duration_days'       => 1,
                'description'         => 'Sewa harian, cocok untuk acara singkat',
                'is_active'           => true,
                'sort_order'          => 1,
                'penalty_percent'     => 15.00,
                'max_penalty_percent' => 100.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Paket 3 Hari',
                'duration_days'       => 3,
                'description'         => 'Paket standar paling populer',
                'is_active'           => true,
                'sort_order'          => 2,
                'penalty_percent'     => 10.00,
                'max_penalty_percent' => 100.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Paket 7 Hari',
                'duration_days'       => 7,
                'description'         => 'Paket mingguan, hemat untuk acara panjang',
                'is_active'           => true,
                'sort_order'          => 3,
                'penalty_percent'     => 8.00,
                'max_penalty_percent' => 80.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Paket Custom',
                'duration_days'       => 0,    // 0 = admin isi manual saat buat transaksi
                'description'         => 'Durasi bebas sesuai kebutuhan',
                'is_active'           => true,
                'sort_order'          => 4,
                'penalty_percent'     => 10.00,
                'max_penalty_percent' => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ]);

        // ── 5. Update settings: hapus fine_per_day lama (diganti paket) ───────
        DB::table('settings')->updateOrInsert(
            ['key' => 'default_package_id'],
            ['value' => '2', 'updated_at' => $now, 'created_at' => $now] // default Paket 3 Hari
        );
    }

    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('package_duration_days');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });

        Schema::dropIfExists('rental_packages');

        DB::table('settings')->where('key', 'default_package_id')->delete();
    }
};
