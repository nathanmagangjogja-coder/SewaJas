<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_schedules', function (Blueprint $table) {
            $table->integer('cooldown_hours')->default(24)->after('target_audience');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_schedules', function (Blueprint $table) {
            $table->dropColumn('cooldown_hours');
        });
    }
};