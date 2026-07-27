<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('id_number', 50)->nullable()->comment('NIK KTP/SIM');
            $table->string('photo')->nullable();
            $table->string('id_photo')->nullable()->comment('Foto KTP/SIM');
            $table->string('chest', 10)->nullable()->comment('Ukuran dada');
            $table->string('waist', 10)->nullable()->comment('Ukuran pinggang');
            $table->string('hip', 10)->nullable()->comment('Ukuran pinggul');
            $table->string('height', 10)->nullable()->comment('Tinggi badan');
            $table->string('weight', 10)->nullable()->comment('Berat badan');
            $table->string('suit_size', 10)->nullable()->comment('Ukuran jas');
            $table->string('shirt_size', 10)->nullable()->comment('Ukuran kemeja');
            $table->string('trouser_size', 10)->nullable()->comment('Ukuran celana');
            $table->string('shoe_size', 10)->nullable()->comment('Ukuran sepatu');
            $table->text('body_notes')->nullable()->comment('Catatan ukuran badan');
            $table->text('notes')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};