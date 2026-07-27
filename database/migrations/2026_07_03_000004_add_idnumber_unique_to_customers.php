<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cleanup any existing duplicates first (keep lowest id)
        $duplicateGroups = DB::select(<<<'SQL'
            select
                regexp_replace(coalesce(id_number, ''), '[^0-9A-Za-z]', '') as norm_id,
                min(id) as keep_id,
                group_concat(id order by id separator ',') as ids,
                count(*) as total
            from customers
            where id_number is not null and id_number != ''
            group by norm_id
            having count(*) > 1
        SQL
        );

        foreach ($duplicateGroups as $group) {
            $ids = array_filter(explode(',', $group->ids));
            $keepId = (int) $group->keep_id;
            $removeIds = array_filter($ids, fn ($id) => (int) $id !== $keepId);

            if (empty($removeIds)) continue;

            // Reassign rentals to keepId
            DB::table('rentals')
                ->whereIn('customer_id', $removeIds)
                ->update(['customer_id' => $keepId]);

            // Permanently delete duplicates (they will be re-created as needed by app)
            DB::table('customers')->whereIn('id', $removeIds)->delete();
        }

        $colExists = $this->columnExists('id_number_normalized');
        $idxExists = $this->indexExists('unique_customer_idnumber');

        if (!$colExists) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('id_number_normalized', 100)
                    ->storedAs("REGEXP_REPLACE(COALESCE(id_number, ''), '[^0-9A-Za-z]', '')");
            });
        }

        if (!$idxExists) {
            DB::statement('ALTER TABLE customers ADD UNIQUE INDEX unique_customer_idnumber (id_number_normalized)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('unique_customer_idnumber')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('unique_customer_idnumber');
            });
        }

        if ($this->columnExists('id_number_normalized')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('id_number_normalized');
            });
        }
    }

    private function columnExists(string $column): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('column_name', $column)
            ->exists();
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('index_name', $indexName)
            ->exists();
    }
};
