<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('created_by')->constrained('users');
            $table->date('rental_date');
            $table->date('return_due_date');
            $table->date('actual_return_date')->nullable();
            $table->integer('duration_days');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->enum('rental_status', ['waiting', 'active', 'overdue', 'returned', 'cancelled'])->default('waiting');
            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'rental_status']);
            $table->index(['rental_date', 'return_due_date']);
            $table->index('invoice_number');
        });

        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');
            $table->string('product_size', 20)->nullable();
            $table->string('product_color', 50)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price_per_day', 12, 2);
            $table->integer('duration_days');
            $table->decimal('subtotal', 12, 2);
            $table->enum('return_condition', ['good', 'damaged', 'lost'])->nullable();
            $table->decimal('damage_fee', 12, 2)->default(0);
            $table->text('return_notes')->nullable();
            $table->boolean('is_returned')->default(false);
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guarantees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->enum('type', ['ktp', 'sim', 'deposit', 'custom']);
            $table->string('id_number', 100)->nullable();
            $table->string('id_name', 150)->nullable();
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['held', 'returned', 'forfeited'])->default('held');
            $table->text('notes')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals');
            $table->foreignId('received_by')->constrained('users');
            $table->string('payment_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'transfer', 'qris', 'other'])->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->enum('type', ['rental', 'deposit', 'late_fee', 'damage_fee', 'refund'])->default('rental');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('guarantees');
        Schema::dropIfExists('rental_items');
        Schema::dropIfExists('rentals');
    }
};