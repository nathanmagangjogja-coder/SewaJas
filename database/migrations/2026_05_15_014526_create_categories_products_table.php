<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('code', 50)->unique()->comment('Kode produk');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('size', 20)->nullable()->comment('S, M, L, XL, XXL, 36, 37...');
            $table->string('color', 50)->nullable();
            $table->string('brand', 100)->nullable();
            $table->decimal('rental_price', 12, 2)->comment('Harga sewa per hari');
            $table->decimal('deposit_price', 12, 2)->default(0)->comment('Deposit jaminan');
            $table->integer('stock_total')->default(1);
            $table->integer('stock_available')->default(1);
            $table->string('photo')->nullable();
            $table->string('qr_code')->nullable()->comment('Path QR code image');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor'])->default('excellent');
            $table->enum('status', ['available', 'rented', 'maintenance', 'inactive'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};