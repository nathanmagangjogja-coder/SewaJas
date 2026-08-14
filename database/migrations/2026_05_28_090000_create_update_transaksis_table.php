<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('dikembalikan_at')->nullable()->after('returned_at');
            $table->timestamp('mulai_laundry_at')->nullable()->after('dikembalikan_at');
            $table->timestamp('selesai_laundry_at')->nullable()->after('mulai_laundry_at');
        });

        // Only run raw ALTER statements on MySQL. SQLite doesn't support MODIFY/ENUM.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN rental_status ENUM(
                'waiting',
                'active',
                'overdue',
                'returned',
                'cancelled',
                'menunggu_laundry',
                'dalam_laundry',
                'siap_disewakan'
            ) NOT NULL DEFAULT 'waiting'");
        }
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['dikembalikan_at', 'mulai_laundry_at', 'selesai_laundry_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rentals MODIFY COLUMN rental_status ENUM(
                'waiting',
                'active',
                'overdue',
                'returned',
                'cancelled'
            ) NOT NULL DEFAULT 'waiting'");
        }
    }
};