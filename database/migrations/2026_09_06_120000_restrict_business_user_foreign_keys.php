<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $foreignKeys = [
        'branches' => 'user_id',
        'check_inventory' => 'user_id',
        'clients' => 'user_id',
        'companies' => 'user_id',
        'products' => 'user_id',
        'sgo_transactions' => 'user_id',
        'storages' => 'user_id',
        'transactions' => 'user_id',
    ];

    public function up(): void
    {
        $this->replaceDeleteRule('restrict');
    }

    public function down(): void
    {
        $this->replaceDeleteRule('cascade');
    }

    private function replaceDeleteRule(string $rule): void
    {
        foreach ($this->foreignKeys as $tableName => $columnName) {
            if (! Schema::hasTable($tableName)
                || ! Schema::hasColumn($tableName, $columnName)
                || ! $this->foreignKeyExists($tableName, $columnName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnName, $rule): void {
                $table->dropForeign([$columnName]);

                $foreign = $table->foreign($columnName)
                    ->references('id')
                    ->on('users');

                if ($rule === 'cascade') {
                    $foreign->cascadeOnDelete();
                } else {
                    $foreign->restrictOnDelete();
                }
            });
        }
    }

    private function foreignKeyExists(string $tableName, string $columnName): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->where('REFERENCED_TABLE_NAME', 'users')
            ->where('REFERENCED_COLUMN_NAME', 'id')
            ->exists();
    }
};
