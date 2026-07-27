<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rentals')->whereNull('public_token')->orderBy('id')->chunkById(200, function ($rentals) {
            foreach ($rentals as $rental) {
                DB::table('rentals')->where('id', $rental->id)
                    ->update(['public_token' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak di-rollback (mengosongkan lagi public_token akan
        // merusak link invoice/WA yang mungkin sudah terlanjur dikirim ke
        // customer memakai token hasil backfill ini).
    }
};
