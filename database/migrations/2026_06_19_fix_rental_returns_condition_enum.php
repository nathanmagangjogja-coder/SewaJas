<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_returns', function (Blueprint $table) {
            $table->enum('condition', ['good', 'damaged', 'lost'])
                  ->default('good')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('rental_returns', function (Blueprint $table) {
            $table->enum('condition', ['baik', 'kurang_baik', 'rusak_ringan', 'rusak_berat'])
                  ->default('baik')
                  ->change();
        });
    }
};
