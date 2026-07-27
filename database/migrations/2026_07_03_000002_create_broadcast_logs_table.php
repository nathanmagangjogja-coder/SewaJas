<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_schedule_id')->constrained('broadcast_schedules')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedInteger('template_index');
            $table->text('message_sent');
            $table->timestamp('sent_at');
            $table->enum('status', ['sent', 'failed', 'skipped']);
            $table->text('fonnte_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_logs');
    }
};
