<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom 'type' di tabel notifications masih ENUM dengan daftar tetap
        // (rental_new, rental_return, rental_late, payment, reminder, system).
        // SupportMessageController mem-broadcast tipe baru 'support_message'
        // yang belum ada di enum ini -> akan gagal dengan error
        // "Data truncated for column 'type'" kalau tidak ditambahkan.
        DB::statement("
            ALTER TABLE notifications
            MODIFY COLUMN type ENUM(
                'rental_new',
                'rental_return',
                'rental_late',
                'payment',
                'reminder',
                'system',
                'support_message'
            ) NOT NULL DEFAULT 'system'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE notifications
            MODIFY COLUMN type ENUM(
                'rental_new',
                'rental_return',
                'rental_late',
                'payment',
                'reminder',
                'system'
            ) NOT NULL DEFAULT 'system'
        ");
    }
};
