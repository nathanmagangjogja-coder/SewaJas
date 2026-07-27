<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. SETTINGS: buat tabel jika belum ada ─────────────────────
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'fine_per_day'],
            ['value' => '10000', 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'rental_duration_days'],
            ['value' => '3', 'updated_at' => now(), 'created_at' => now()]
        );

        // ─── 2. RENTALS: tambah kolom return_condition & return_notes ────
        Schema::table('rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('rentals', 'return_condition')) {
                $table->enum('return_condition', [
                    'baik', 'kurang_baik', 'rusak_ringan', 'rusak_berat'
                ])->nullable()->after('returned_at');
            }
            if (!Schema::hasColumn('rentals', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('return_condition');
            }
        });

        // ─── 3. RENTAL_RETURNS: buat tabel baru ──────────────────────────
        if (!Schema::hasTable('rental_returns')) {
            Schema::create('rental_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
                $table->date('returned_at');
                $table->unsignedInteger('late_days')->default(0);
                $table->unsignedBigInteger('late_fee')->default(0);
                $table->enum('condition', [
                    'baik', 'kurang_baik', 'rusak_ringan', 'rusak_berat'
                ])->default('baik');
                $table->text('return_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_returns');
        Schema::dropIfExists('settings');

        Schema::table('rentals', function (Blueprint $table) {
            if (Schema::hasColumn('rentals', 'return_condition')) {
                $table->dropColumn('return_condition');
            }
            if (Schema::hasColumn('rentals', 'return_notes')) {
                $table->dropColumn('return_notes');
            }
        });
    }
};