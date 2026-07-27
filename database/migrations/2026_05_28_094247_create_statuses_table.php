<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laundries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('rentals')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('products')->onDelete('cascade');
            $table->enum('status', [
                'menunggu_laundry',
                'dalam_laundry',
                'siap_disewakan'
            ])->default('menunggu_laundry');
            $table->timestamp('dikembalikan_at')->nullable();
            $table->timestamp('mulai_laundry_at')->nullable();
            $table->timestamp('selesai_laundry_at')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('status_lama')->nullable();
            $table->string('status_baru');
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->index(['model_type', 'model_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_histories');
        Schema::dropIfExists('laundries');
    }
};