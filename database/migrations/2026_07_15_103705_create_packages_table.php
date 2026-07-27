<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->text('description')->nullable();

            $table->unsignedInteger('duration_days')->default(3);

            $table->unsignedInteger('sort_order')->default(1);

            $table->decimal('penalty_percent',5,2)->default(10);

            $table->decimal('max_penalty_percent',5,2)->nullable();

            $table->boolean('is_custom')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};