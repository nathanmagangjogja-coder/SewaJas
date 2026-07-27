<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('rentals', function (Blueprint $table) {
        $table->unsignedInteger('overdue_days')->default(0)->after('late_fee');
    });
}

public function down()
{
    Schema::table('rentals', function (Blueprint $table) {
        $table->dropColumn('overdue_days');
    });
}
};
