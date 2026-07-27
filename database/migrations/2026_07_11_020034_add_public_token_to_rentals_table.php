<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('invoice_number');
        });

        // Backfill token untuk data yang sudah ada, supaya invoice lama tetap bisa diakses
        DB::table('rentals')->whereNull('public_token')->orderBy('id')->chunkById(200, function ($rentals) {
            foreach ($rentals as $rental) {
                DB::table('rentals')
                    ->where('id', $rental->id)
                    ->update(['public_token' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
