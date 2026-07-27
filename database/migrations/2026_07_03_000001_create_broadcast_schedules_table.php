<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->json('send_at_times');
            $table->json('message_templates');
            $table->boolean('is_active')->default(true);
            $table->enum('target_audience', ['all', 'active_renters', 'overdue', 'returning_soon'])->default('all');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_schedules');
    }
};
