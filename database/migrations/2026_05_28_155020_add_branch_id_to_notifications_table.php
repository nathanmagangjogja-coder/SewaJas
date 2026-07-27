<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  // database/migrations/xxxx_add_branch_id_to_notifications_table.php
public function up(): void
{
    Schema::table('notifications', function (Blueprint $table) {
        $table->unsignedBigInteger('branch_id')->nullable()->after('id');
        $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('notifications', function (Blueprint $table) {
        $table->dropForeign(['branch_id']);
        $table->dropColumn('branch_id');
    });
}
};
