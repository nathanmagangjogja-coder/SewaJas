<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // database/migrations/xxxx_create_rental_returns_table.php
Schema::create('rental_returns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
    $table->date('returned_at');
    $table->integer('late_days')->default(0);
    $table->unsignedInteger('late_fee')->default(0);
    $table->enum('condition', ['baik', 'kurang_baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
    $table->text('return_notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_returns');
    }
};
