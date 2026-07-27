<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Normalize and remove duplicates by phone (keep lowest id)
        $phoneGroups = DB::select(<<<'SQL'
            select
                regexp_replace(phone, '[^0-9]', '') as norm_phone,
                min(id) as keep_id,
                group_concat(id order by id separator ',') as ids,
                count(*) as total
            from customers
            where phone is not null and phone != ''
            group by norm_phone
            having count(*) > 1
        SQL
        );

        foreach ($phoneGroups as $g) {
            $ids = array_filter(explode(',', $g->ids));
            $keepId = (int) $g->keep_id;
            $removeIds = array_filter($ids, fn($id) => (int)$id !== $keepId);
            if (empty($removeIds)) continue;

            DB::table('rentals')->whereIn('customer_id', $removeIds)->update(['customer_id' => $keepId]);
            DB::table('customers')->whereIn('id', $removeIds)->delete();
        }

        // 2) Normalize and remove duplicates by name (keep lowest id)
        $nameGroups = DB::select(<<<'SQL'
            select
                lower(trim(regexp_replace(name, '\\s+', ' '))) as norm_name,
                min(id) as keep_id,
                group_concat(id order by id separator ',') as ids,
                count(*) as total
            from customers
            where name is not null and name != ''
            group by norm_name
            having count(*) > 1
        SQL
        );

        foreach ($nameGroups as $g) {
            $ids = array_filter(explode(',', $g->ids));
            $keepId = (int) $g->keep_id;
            $removeIds = array_filter($ids, fn($id) => (int)$id !== $keepId);
            if (empty($removeIds)) continue;

            DB::table('rentals')->whereIn('customer_id', $removeIds)->update(['customer_id' => $keepId]);
            DB::table('customers')->whereIn('id', $removeIds)->delete();
        }

        // Ensure generated columns exist (they may have been created earlier migrations)
        $nameCol = $this->columnExists('name_normalized');
        $phoneCol = $this->columnExists('phone_normalized');

        if (!$nameCol || !$phoneCol) {
            Schema::table('customers', function (Blueprint $table) use ($nameCol, $phoneCol) {
                if (!$nameCol) {
                    $table->string('name_normalized', 191)
                        ->storedAs("LOWER(TRIM(REGEXP_REPLACE(name, '\\s+', ' ')))");
                }
                if (!$phoneCol) {
                    $table->string('phone_normalized', 20)
                        ->storedAs("REGEXP_REPLACE(phone, '[^0-9]', '')");
                }
            });
        }

        // Add unique indexes if missing
        if (!$this->indexExists('unique_customer_phone')) {
            DB::statement('ALTER TABLE customers ADD UNIQUE INDEX unique_customer_phone (phone_normalized)');
        }
        if (!$this->indexExists('unique_customer_name')) {
            DB::statement('ALTER TABLE customers ADD UNIQUE INDEX unique_customer_name (name_normalized)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('unique_customer_phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('unique_customer_phone');
            });
        }
        if ($this->indexExists('unique_customer_name')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('unique_customer_name');
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
