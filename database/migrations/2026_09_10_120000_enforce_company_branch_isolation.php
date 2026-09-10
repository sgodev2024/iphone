<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'branch_id')) {
            throw new RuntimeException(
                'Supplier isolation requires companies.branch_id to exist.'
            );
        }

        if (DB::table('companies')->whereNull('branch_id')->exists()) {
            throw new RuntimeException(
                'Cannot enforce Supplier Branch ownership while companies.branch_id contains NULL.'
            );
        }

        $duplicate = DB::table('companies')
            ->select(['branch_id', 'name'])
            ->groupBy('branch_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->first();
        if ($duplicate !== null) {
            throw new RuntimeException(
                'Cannot enforce Supplier Branch ownership while duplicate names exist in Branch '
                .$duplicate->branch_id.': '.$duplicate->name
            );
        }

        if ($this->hasProductSupplierMismatch()) {
            throw new RuntimeException(
                'Cannot enforce Supplier Branch ownership while Product-Supplier cross-Branch links exist.'
            );
        }

        if ($this->hasImportSupplierMismatch()) {
            throw new RuntimeException(
                'Cannot enforce Supplier Branch ownership while Import-Supplier cross-Branch links exist.'
            );
        }

        if (DB::getDriverName() === 'mysql' && $this->hasBranchForeignKey()) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->dropForeign('companies_branch_id_foreign');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `companies` MODIFY `branch_id` BIGINT UNSIGNED NOT NULL');
        } elseif (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteCompanies();
        } else {
            Schema::table('companies', function (Blueprint $table): void {
                $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            });
        }

        if (! $this->hasIndex('companies_branch_id_index')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->index('branch_id', 'companies_branch_id_index');
            });
        }

        if (! $this->hasBranchForeignKey()) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->foreign('branch_id', 'companies_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->restrictOnDelete();
            });
        }

        if (! $this->hasIndex('companies_branch_name_unique')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->unique(['branch_id', 'name'], 'companies_branch_name_unique');
            });
        }
    }

    public function down(): void
    {
        // Intentionally forward-only: restoring nullable Supplier ownership would
        // re-open a security boundary and cannot be rolled back safely.
    }

    private function rebuildSqliteCompanies(): void
    {
        $table = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'companies'"
        );
        if ($table === null || empty($table->sql)) {
            throw new RuntimeException('Cannot inspect the SQLite companies table.');
        }

        $schemaObjects = DB::select(
            "SELECT sql FROM sqlite_master
             WHERE tbl_name = 'companies'
               AND type IN ('index', 'trigger')
               AND sql IS NOT NULL
             ORDER BY type, name"
        );
        $columns = collect(DB::select("PRAGMA table_info('companies')"))
            ->pluck('name')
            ->map(fn (string $column): string => '"'.str_replace('"', '""', $column).'"')
            ->implode(', ');

        $createSql = preg_replace(
            '/^CREATE TABLE\s+(?:"companies"|`companies`|\[companies\]|companies)/i',
            'CREATE TABLE "companies_branch_rebuild"',
            $table->sql,
            1
        );
        $createSql = preg_replace_callback(
            '/("branch_id"\s+[^,]+)(?=,)/i',
            static fn (array $match): string => preg_match('/\bNOT\s+NULL\b/i', $match[1])
                ? $match[1]
                : rtrim($match[1]).' NOT NULL',
            $createSql,
            1
        );
        $createSql = preg_replace(
            '/(foreign key\s*\(\s*"branch_id"\s*\)\s*references\s*"branches"\s*\(\s*"id"\s*\))(?:\s+on delete\s+(?:set null|restrict|cascade|no action))?/i',
            '$1 on delete restrict',
            $createSql,
            1
        );

        if ($createSql === null || ! str_contains($createSql, 'companies_branch_rebuild')) {
            throw new RuntimeException('Cannot rebuild the SQLite companies table safely.');
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        try {
            DB::transaction(function () use ($createSql, $columns, $schemaObjects): void {
                DB::statement($createSql);
                DB::statement(
                    'INSERT INTO "companies_branch_rebuild" ('.$columns.') '
                    .'SELECT '.$columns.' FROM "companies"'
                );
                DB::statement('DROP TABLE "companies"');
                DB::statement('ALTER TABLE "companies_branch_rebuild" RENAME TO "companies"');

                foreach ($schemaObjects as $schemaObject) {
                    DB::statement($schemaObject->sql);
                }
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
    private function hasProductSupplierMismatch(): bool
    {
        if (! Schema::hasTable('company_product')
            || ! Schema::hasColumn('company_product', 'product_id')
            || ! Schema::hasColumn('company_product', 'company_id')
            || ! Schema::hasColumn('products', 'branch_id')
        ) {
            return false;
        }

        return DB::table('company_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->join('companies as c', 'c.id', '=', 'cp.company_id')
            ->whereRaw('(p.branch_id IS NULL OR c.branch_id IS NULL OR p.branch_id <> c.branch_id)')
            ->exists();
    }

    private function hasImportSupplierMismatch(): bool
    {
        if (! Schema::hasTable('import_coupon')
            || ! Schema::hasColumn('import_coupon', 'storage_id')
            || ! Schema::hasColumn('import_coupon', 'companies_id')
            || ! Schema::hasColumn('storages', 'branch_id')
        ) {
            return false;
        }

        return DB::table('import_coupon as i')
            ->join('storages as s', 's.id', '=', 'i.storage_id')
            ->join('companies as c', 'c.id', '=', 'i.companies_id')
            ->whereRaw('(s.branch_id IS NULL OR c.branch_id IS NULL OR s.branch_id <> c.branch_id)')
            ->exists();
    }

    private function hasBranchForeignKey(): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'companies')
                ->where('COLUMN_NAME', 'branch_id')
                ->where('REFERENCED_TABLE_NAME', 'branches')
                ->exists();
        }

        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA foreign_key_list('companies')"))
                ->contains(fn (object $foreign): bool =>
                    ($foreign->from ?? null) === 'branch_id'
                    && ($foreign->table ?? null) === 'branches'
                );
        }

        return false;
    }

    private function hasIndex(string $name): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::table('information_schema.STATISTICS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'companies')
                ->where('INDEX_NAME', $name)
                ->exists();
        }

        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('companies')"))
                ->contains(fn (object $index): bool => ($index->name ?? null) === $name);
        }

        return false;
    }
};