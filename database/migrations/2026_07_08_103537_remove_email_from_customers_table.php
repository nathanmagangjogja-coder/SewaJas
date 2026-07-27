<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom email sudah terhapus manual — tidak perlu dropColumn
        // Migration ini hanya untuk sinkronisasi state Laravel
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });
    }
};