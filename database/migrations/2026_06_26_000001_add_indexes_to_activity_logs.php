<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $existing = collect(DB::select("SHOW INDEX FROM activity_logs"))->pluck('Key_name')->toArray();

            if (!in_array('idx_activity_action', $existing)) {
                $table->index('action', 'idx_activity_action');
            }
            if (!in_array('idx_activity_branch', $existing)) {
                $table->index('branch_id', 'idx_activity_branch');
            }
            if (!in_array('idx_activity_created', $existing)) {
                $table->index('created_at', 'idx_activity_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            foreach (['idx_activity_action','idx_activity_branch','idx_activity_created'] as $idx) {
                try { $table->dropIndex($idx); } catch (\Exception $e) {}
            }
        });
    }
};
