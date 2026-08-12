<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureTransactionsTypeExists();

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $definition = $this->typeDefinition();
        $values = $this->enumValues($definition->Type);

        if (in_array('sale', $values, true)) {
            return;
        }

        $values[] = 'sale';
        $this->modifyTypeEnum($definition, $values);
    }

    public function down(): void
    {
        $this->ensureTransactionsTypeExists();

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('transactions')->where('type', 'sale')->exists()) {
            throw new RuntimeException(
                'Cannot remove transactions.type=sale while sale transactions still exist.'
            );
        }

        $definition = $this->typeDefinition();
        $values = $this->enumValues($definition->Type);

        if (! in_array('sale', $values, true)) {
            return;
        }

        $values = array_values(array_filter(
            $values,
            fn (string $value): bool => $value !== 'sale'
        ));

        $this->modifyTypeEnum($definition, $values);
    }

    private function ensureTransactionsTypeExists(): void
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'type')) {
            throw new RuntimeException('The transactions.type column does not exist.');
        }
    }

    private function typeDefinition(): object
    {
        $definition = DB::selectOne(
            "SHOW FULL COLUMNS FROM `transactions` LIKE 'type'"
        );

        if (! $definition) {
            throw new RuntimeException('Unable to inspect transactions.type.');
        }

        if (trim((string) ($definition->Extra ?? '')) !== '') {
            throw new RuntimeException(
                'Cannot safely alter transactions.type because it has unsupported extra attributes.'
            );
        }

        return $definition;
    }

    /**
     * @return list<string>
     */
    private function enumValues(string $type): array
    {
        if (! preg_match('/^enum\((.*)\)$/i', $type, $matches)) {
            throw new RuntimeException("transactions.type is not an enum: {$type}");
        }

        $values = str_getcsv($matches[1], ',', "'", '\\');

        if ($values === []) {
            throw new RuntimeException('transactions.type has no enum values.');
        }

        return $values;
    }

    /**
     * @param  list<string>  $values
     */
    private function modifyTypeEnum(object $definition, array $values): void
    {
        $pdo = DB::connection()->getPdo();
        $enum = implode(', ', array_map(
            fn (string $value): string => $pdo->quote($value),
            $values
        ));
        $nullable = strtoupper((string) $definition->Null) === 'YES';
        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        $defaultSql = $definition->Default === null
            ? ($nullable ? 'DEFAULT NULL' : '')
            : 'DEFAULT '.$pdo->quote((string) $definition->Default);
        $collation = (string) ($definition->Collation ?? '');
        $collationSql = '';

        if ($collation !== '') {
            if (! preg_match('/^[a-zA-Z0-9_]+$/', $collation)) {
                throw new RuntimeException('transactions.type has an unsafe collation definition.');
            }

            $characterSet = strstr($collation, '_', true);

            if ($characterSet === false || $characterSet === '') {
                throw new RuntimeException('Unable to resolve transactions.type character set.');
            }

            $collationSql = "CHARACTER SET {$characterSet} COLLATE {$collation}";
        }

        $commentSql = 'COMMENT '.$pdo->quote((string) ($definition->Comment ?? ''));
        $parts = array_filter([
            "ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM({$enum})",
            $collationSql,
            $nullSql,
            $defaultSql,
            $commentSql,
        ]);

        DB::statement(implode(' ', $parts));
    }
};
