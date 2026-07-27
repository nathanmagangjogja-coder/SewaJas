<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_logs', function (Blueprint $table) {
            $table->foreignId('broadcast_schedule_id')->nullable()->change();
            $table->unsignedInteger('template_index')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_logs', function (Blueprint $table) {
            // Can't easily revert, but this is safe
        });
    }
};