<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGroups = DB::select(<<<'SQL'
                select
                    lower(trim(regexp_replace(name, '\\s+', ' '))) as normalized_name,
                    regexp_replace(phone, '[^0-9]', '') as normalized_phone,
                    min(id) as keep_id,
                    group_concat(id order by id separator ',') as ids,
                    count(*) as total
                from customers
                group by normalized_name, normalized_phone
                having count(*) > 1
            SQL
            );

        foreach ($duplicateGroups as $group) {
            $ids = array_filter(explode(',', $group->ids));
            $keepId = (int) $group->keep_id;
            $removeIds = array_filter($ids, fn ($id) => (int) $id !== $keepId);

            if (empty($removeIds)) {
                continue;
            }

            DB::table('rentals')
                ->whereIn('customer_id', $removeIds)
                ->update(['customer_id' => $keepId]);

            DB::table('customers')
                ->whereIn('id', $removeIds)
                ->delete();
        }

        $nameColumnExists = $this->columnExists('name_normalized');
        $phoneColumnExists = $this->columnExists('phone_normalized');
        $indexExists = $this->indexExists('unique_customer');

        if (!$nameColumnExists || !$phoneColumnExists) {
            Schema::table('customers', function (Blueprint $table) use ($nameColumnExists, $phoneColumnExists) {
                if (!$nameColumnExists) {
                    $table->string('name_normalized', 191)
                        ->storedAs("LOWER(TRIM(REGEXP_REPLACE(name, '\\s+', ' ')))");
                }
                if (!$phoneColumnExists) {
                    $table->string('phone_normalized', 20)
                        ->storedAs("REGEXP_REPLACE(phone, '[^0-9]', '')");
                }
            });
        }

        if (!$indexExists) {
            DB::statement('ALTER TABLE customers ADD UNIQUE INDEX unique_customer (name_normalized, phone_normalized)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('unique_customer')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('unique_customer');
            });
        }

        if ($this->columnExists('name_normalized') || $this->columnExists('phone_normalized')) {
            Schema::table('customers', function (Blueprint $table) {
                $columns = [];
                if ($this->columnExists('name_normalized')) {
                    $columns[] = 'name_normalized';
                }
                if ($this->columnExists('phone_normalized')) {
                    $columns[] = 'phone_normalized';
                }

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
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
