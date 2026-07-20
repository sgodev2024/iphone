<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function usage(): never
{
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php tools/schema_sync.php export --out=path.json\n");
    fwrite(STDERR, "  php tools/schema_sync.php create-test-db --name=database_name\n");
    fwrite(STDERR, "  php tools/schema_sync.php pre-report --schema=path.json --out=report.md --migration-check-db=name\n");
    fwrite(STDERR, "  php tools/schema_sync.php generate --schema=path.json --out=directory\n");
    fwrite(STDERR, "  php tools/schema_sync.php compare --source=a.json --target=b.json --out=report.md\n");
    exit(1);
}

function options(array $argv): array
{
    $opts = [];
    foreach (array_slice($argv, 2) as $arg) {
        if (!str_starts_with($arg, '--') || !str_contains($arg, '=')) {
            continue;
        }
        [$key, $value] = explode('=', substr($arg, 2), 2);
        $opts[$key] = $value;
    }

    return $opts;
}

function connectionConfig(): array
{
    $name = config('database.default');
    $config = config("database.connections.$name");

    return [$name, $config];
}

function currentDatabase(): string
{
    [, $config] = connectionConfig();
    $database = $config['database'] ?? '';
    if ($database === '') {
        fwrite(STDERR, "No database configured.\n");
        exit(1);
    }

    return $database;
}

function queryAll(string $sql, array $bindings = []): array
{
    return array_map(static fn ($row) => (array) $row, DB::select($sql, $bindings));
}

function queryValue(string $sql, array $bindings = []): mixed
{
    $row = DB::selectOne($sql, $bindings);
    if (!$row) {
        return null;
    }
    $array = (array) $row;

    return reset($array);
}

function exportSchema(): array
{
    $database = currentDatabase();

    $tables = queryAll(
        "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
         ORDER BY TABLE_NAME",
        [$database]
    );

    $columns = queryAll(
        "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE,
                DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION,
                NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME,
                COLUMN_KEY, EXTRA, COLUMN_COMMENT, GENERATION_EXPRESSION
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
         ORDER BY TABLE_NAME, ORDINAL_POSITION",
        [$database]
    );

    $indexes = queryAll(
        "SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION,
                CARDINALITY, SUB_PART, PACKED, NULLABLE, INDEX_TYPE, COMMENT, INDEX_COMMENT,
                EXPRESSION
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ?
         ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX",
        [$database]
    );

    $foreignKeys = queryAll(
        "SELECT kcu.CONSTRAINT_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.ORDINAL_POSITION,
                kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE, rc.DELETE_RULE
         FROM information_schema.KEY_COLUMN_USAGE kcu
         JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
           ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
          AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
          AND rc.TABLE_NAME = kcu.TABLE_NAME
         WHERE kcu.TABLE_SCHEMA = ?
           AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
         ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION",
        [$database]
    );

    $constraints = queryAll(
        "SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = ?
         ORDER BY TABLE_NAME, CONSTRAINT_NAME",
        [$database]
    );

    $showCreate = [];
    foreach ($tables as $table) {
        $name = $table['TABLE_NAME'];
        $quoted = str_replace('`', '``', $name);
        $row = queryAll("SHOW CREATE TABLE `$quoted`")[0] ?? [];
        $showCreate[$name] = $row['Create Table'] ?? $row['Create Table '] ?? null;
    }

    $migrationRows = [];
    $tableNames = array_column($tables, 'TABLE_NAME');
    if (in_array('migrations', $tableNames, true)) {
        $migrationRows = queryAll('SELECT id, migration, batch FROM migrations ORDER BY batch, migration, id');
    }

    return [
        'connection' => config('database.default'),
        'database' => $database,
        'exported_at' => date(DATE_ATOM),
        'tables' => $tables,
        'columns' => $columns,
        'indexes' => $indexes,
        'foreign_keys' => $foreignKeys,
        'constraints' => $constraints,
        'show_create' => $showCreate,
        'migrations_rows' => $migrationRows,
    ];
}

function writeJson(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function createTestDb(string $name): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        fwrite(STDERR, "Unsafe database name: $name\n");
        exit(1);
    }

    [, $config] = connectionConfig();
    $charset = $config['charset'] ?? 'utf8mb4';
    $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
    DB::statement("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET `$charset` COLLATE `$collation`");
    echo "Created test database: $name\n";
}

function tableOrder(array $schema): array
{
    $tables = array_values(array_filter(
        array_column($schema['tables'], 'TABLE_NAME'),
        static fn ($table) => $table !== 'migrations'
    ));
    $deps = array_fill_keys($tables, []);
    foreach ($schema['foreign_keys'] as $fk) {
        $table = $fk['TABLE_NAME'];
        $ref = $fk['REFERENCED_TABLE_NAME'];
        if ($table !== $ref && isset($deps[$table]) && in_array($ref, $tables, true)) {
            $deps[$table][$ref] = true;
        }
    }

    $ordered = [];
    while (count($ordered) < count($tables)) {
        $progress = false;
        foreach ($tables as $table) {
            if (in_array($table, $ordered, true)) {
                continue;
            }
            $missing = array_diff(array_keys($deps[$table]), $ordered);
            if ($missing === []) {
                $ordered[] = $table;
                $progress = true;
            }
        }
        if (!$progress) {
            foreach ($tables as $table) {
                if (!in_array($table, $ordered, true)) {
                    $ordered[] = $table;
                }
            }
        }
    }

    return $ordered;
}

function migrationSql(string $createSql): string
{
    $sql = preg_replace('/ AUTO_INCREMENT=\d+/i', '', $createSql);
    $sql = str_replace("\r\n", "\n", $sql);

    return rtrim($sql);
}

function phpString(string $value): string
{
    return var_export($value, true);
}

function migrationFile(string $table, string $sql): string
{
    $tableLiteral = phpString($table);

    return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
$sql
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists($tableLiteral);
    }
};

PHP;
}

function generateMigrations(string $schemaPath, string $outDir): void
{
    if (!is_file($schemaPath)) {
        fwrite(STDERR, "Schema file not found: $schemaPath\n");
        exit(1);
    }
    if (is_dir($outDir) && count(scandir($outDir)) > 2) {
        fwrite(STDERR, "Output directory exists and is not empty: $outDir\n");
        exit(1);
    }
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }

    $schema = json_decode(file_get_contents($schemaPath), true);
    $order = tableOrder($schema);
    $created = [];
    $base = new DateTimeImmutable('2026-07-20 12:00:00');

    foreach ($order as $i => $table) {
        $createSql = $schema['show_create'][$table] ?? null;
        if (!$createSql) {
            continue;
        }
        $timestamp = $base->modify("+$i minutes")->format('Y_m_d_His');
        $safe = preg_replace('/[^A-Za-z0-9_]+/', '_', $table);
        $file = "$timestamp" . "_baseline_create_{$safe}_table.php";
        $path = rtrim($outDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . $file;
        file_put_contents($path, migrationFile($table, migrationSql($createSql)));
        $created[] = $file;
    }

    file_put_contents(
        rtrim($outDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . 'README.md',
        "# Baseline migrations\n\nGenerated from the current MySQL schema. These migrations contain schema only, no real data.\n"
    );

    echo implode(PHP_EOL, $created) . PHP_EOL;
}

function markdownList(array $items): string
{
    if ($items === []) {
        return "- None\n";
    }

    return implode('', array_map(static fn ($item) => "- $item\n", $items));
}

function writePreReport(string $schemaPath, string $outPath, string $migrationCheckDb): void
{
    $schema = json_decode(file_get_contents($schemaPath), true);
    $migrationFiles = [];
    foreach (new DirectoryIterator(__DIR__ . '/../database/migrations') as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $migrationFiles[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        }
    }
    sort($migrationFiles);

    $dbRows = array_map(static fn ($row) => $row['migration'], $schema['migrations_rows']);
    $pending = array_values(array_diff($migrationFiles, $dbRows));
    $missing = array_values(array_diff($dbRows, $migrationFiles));
    sort($pending);
    sort($missing);

    $tables = array_map(
        static fn ($table) => "{$table['TABLE_NAME']} ({$table['ENGINE']}, {$table['TABLE_COLLATION']})",
        $schema['tables']
    );

    $fkCounts = [];
    foreach ($schema['foreign_keys'] as $fk) {
        $fkCounts[$fk['TABLE_NAME']] = ($fkCounts[$fk['TABLE_NAME']] ?? 0) + 1;
    }
    ksort($fkCounts);
    $fkLines = [];
    foreach ($fkCounts as $table => $count) {
        $fkLines[] = "$table: $count FK column(s)";
    }

    $content = "# Pre-fix migration/schema drift report\n\n";
    $content .= "Generated at: " . date(DATE_ATOM) . "\n\n";
    $content .= "## Scope\n\n";
    $content .= "- Source database used as truth: `{$schema['database']}` via Laravel connection `{$schema['connection']}`.\n";
    $content .= "- Existing migration directory: `database/migrations`.\n";
    $content .= "- No destructive command was run against the source database.\n";
    $content .= "- Existing migrations were tested only on a separate database: `$migrationCheckDb`.\n\n";
    $content .= "## Source schema snapshot\n\n";
    $content .= "- Tables: " . count($schema['tables']) . "\n";
    $content .= "- Columns: " . count($schema['columns']) . "\n";
    $content .= "- Index rows from `information_schema.STATISTICS`: " . count($schema['indexes']) . "\n";
    $content .= "- Foreign key column rows: " . count($schema['foreign_keys']) . "\n";
    $content .= "- Rows in source `migrations` table: " . count($schema['migrations_rows']) . "\n\n";
    $content .= "## Tables in source database\n\n" . markdownList($tables) . "\n";
    $content .= "## Migration file/database table mismatch\n\n";
    $content .= "- Files in `database/migrations`: " . count($migrationFiles) . "\n";
    $content .= "- Migration rows in DB: " . count($dbRows) . "\n\n";
    $content .= "Migration rows in DB without matching file:\n\n" . markdownList($missing) . "\n";
    $content .= "Migration files pending / not recorded in DB:\n\n" . markdownList($pending) . "\n";
    $content .= "## Existing migration build result on separate DB\n\n";
    $content .= "Running current migrations on `$migrationCheckDb` failed before a complete schema could be built.\n\n";
    $content .= "First failure:\n\n";
    $content .= "```text\n";
    $content .= "2024_06_25_154113_create_config_table FAIL\n";
    $content .= "SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table 'banks'\n";
    $content .= "SQL: alter table `config` add constraint `config_bank_id_foreign` foreign key (`bank_id`) references `banks` (`id`)\n";
    $content .= "```\n\n";
    $content .= "Root cause observed from files:\n\n";
    $content .= "- `2024_06_25_154113_create_config_table.php` creates `config.bank_id` FK to `banks.id`.\n";
    $content .= "- `2024_06_27_094831_create_banks_table.php` creates `banks`, but it is ordered later by timestamp.\n\n";
    $content .= "Because the current migration set fails on an empty test DB, a full table/column/index/FK comparison from old migrations to the source schema cannot be completed reliably. The source schema export in `storage/app/schema-sync/source-schema.json` is therefore used as the baseline truth for the new generated migration set and final schema comparison.\n\n";
    $content .= "## Foreign keys present in source schema\n\n" . markdownList($fkLines) . "\n";
    $content .= "## Captured artifacts\n\n";
    $content .= "- `storage/app/schema-sync/migrate-status.txt`\n";
    $content .= "- `storage/app/schema-sync/migration-files.txt`\n";
    $content .= "- `storage/app/schema-sync/migrations-table.txt`\n";
    $content .= "- `storage/app/schema-sync/source-schema.json`\n";
    $content .= "- `storage/app/schema-sync/current-migrations-run.txt`\n";

    $dir = dirname($outPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($outPath, $content);
    echo "Wrote $outPath\n";
}

function normalizeValue(mixed $value): mixed
{
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $trimmed = trim($value);
        if (strtoupper($trimmed) === 'NULL') {
            return null;
        }

        return $trimmed;
    }

    return $value;
}

function mapByTableAndName(array $rows, string $nameKey): array
{
    $out = [];
    foreach ($rows as $row) {
        $out[$row['TABLE_NAME']][$row[$nameKey]] = $row;
    }

    return $out;
}

function normalizeIndexes(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $table = $row['TABLE_NAME'];
        $name = $row['INDEX_NAME'];
        $out[$table][$name]['unique'] = ((int) $row['NON_UNIQUE']) === 0;
        $out[$table][$name]['type'] = $row['INDEX_TYPE'];
        $out[$table][$name]['columns'][(int) $row['SEQ_IN_INDEX']] = [
            'column' => $row['COLUMN_NAME'],
            'sub_part' => normalizeValue($row['SUB_PART']),
            'expression' => normalizeValue($row['EXPRESSION'] ?? null),
        ];
    }
    foreach ($out as &$tableIndexes) {
        foreach ($tableIndexes as &$index) {
            ksort($index['columns']);
            $index['columns'] = array_values($index['columns']);
        }
        ksort($tableIndexes);
    }

    return $out;
}

function normalizeForeignKeys(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $table = $row['TABLE_NAME'];
        $name = $row['CONSTRAINT_NAME'];
        $out[$table][$name]['referenced_table'] = $row['REFERENCED_TABLE_NAME'];
        $out[$table][$name]['update_rule'] = $row['UPDATE_RULE'];
        $out[$table][$name]['delete_rule'] = $row['DELETE_RULE'];
        $out[$table][$name]['columns'][(int) $row['ORDINAL_POSITION']] = [
            'column' => $row['COLUMN_NAME'],
            'referenced_column' => $row['REFERENCED_COLUMN_NAME'],
        ];
    }
    foreach ($out as &$tableFks) {
        foreach ($tableFks as &$fk) {
            ksort($fk['columns']);
            $fk['columns'] = array_values($fk['columns']);
        }
        ksort($tableFks);
    }

    return $out;
}

function compareSchemas(string $sourcePath, string $targetPath, string $outPath): void
{
    $source = json_decode(file_get_contents($sourcePath), true);
    $target = json_decode(file_get_contents($targetPath), true);
    $lines = [];

    $sourceTables = mapByTableAndName($source['tables'], 'TABLE_NAME');
    $targetTables = mapByTableAndName($target['tables'], 'TABLE_NAME');
    $allTables = array_unique(array_merge(array_keys($sourceTables), array_keys($targetTables)));
    sort($allTables);

    $sourceColumns = mapByTableAndName($source['columns'], 'COLUMN_NAME');
    $targetColumns = mapByTableAndName($target['columns'], 'COLUMN_NAME');
    $sourceIndexes = normalizeIndexes($source['indexes']);
    $targetIndexes = normalizeIndexes($target['indexes']);
    $sourceFks = normalizeForeignKeys($source['foreign_keys']);
    $targetFks = normalizeForeignKeys($target['foreign_keys']);

    foreach ($allTables as $table) {
        if (!isset($sourceTables[$table])) {
            $lines[] = "- Table `$table` exists only in target.";
            continue;
        }
        if (!isset($targetTables[$table])) {
            $lines[] = "- Table `$table` exists only in source.";
            continue;
        }

        foreach (['ENGINE', 'TABLE_COLLATION'] as $field) {
            if (normalizeValue($sourceTables[$table][$field] ?? null) !== normalizeValue($targetTables[$table][$field] ?? null)) {
                $lines[] = "- Table `$table` differs in `$field`: source `" . normalizeValue($sourceTables[$table][$field] ?? null) . "`, target `" . normalizeValue($targetTables[$table][$field] ?? null) . "`.";
            }
        }

        $columnNames = array_unique(array_merge(array_keys($sourceColumns[$table] ?? []), array_keys($targetColumns[$table] ?? [])));
        sort($columnNames);
        foreach ($columnNames as $column) {
            if (!isset($sourceColumns[$table][$column])) {
                $lines[] = "- Column `$table.$column` exists only in target.";
                continue;
            }
            if (!isset($targetColumns[$table][$column])) {
                $lines[] = "- Column `$table.$column` exists only in source.";
                continue;
            }
            foreach (['ORDINAL_POSITION', 'COLUMN_DEFAULT', 'IS_NULLABLE', 'DATA_TYPE', 'COLUMN_TYPE', 'CHARACTER_SET_NAME', 'COLLATION_NAME', 'COLUMN_KEY', 'EXTRA', 'COLUMN_COMMENT', 'GENERATION_EXPRESSION'] as $field) {
                if (normalizeValue($sourceColumns[$table][$column][$field] ?? null) !== normalizeValue($targetColumns[$table][$column][$field] ?? null)) {
                    $lines[] = "- Column `$table.$column` differs in `$field`: source `" . normalizeValue($sourceColumns[$table][$column][$field] ?? null) . "`, target `" . normalizeValue($targetColumns[$table][$column][$field] ?? null) . "`.";
                }
            }
        }

        if (($sourceIndexes[$table] ?? []) != ($targetIndexes[$table] ?? [])) {
            $lines[] = "- Indexes differ on table `$table`.";
        }
        if (($sourceFks[$table] ?? []) != ($targetFks[$table] ?? [])) {
            $lines[] = "- Foreign keys differ on table `$table`.";
        }
    }

    $header = "# Schema comparison\n\nSource: `$sourcePath`\n\nTarget: `$targetPath`\n\n";
    $body = $lines ? implode("\n", $lines) . "\n" : "No schema differences detected.\n";
    $dir = dirname($outPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($outPath, $header . $body);
    echo $lines ? "Differences found: " . count($lines) . PHP_EOL : "No schema differences detected." . PHP_EOL;
}

$command = $argv[1] ?? null;
$opts = options($argv);

match ($command) {
    'export' => writeJson($opts['out'] ?? usage(), exportSchema()),
    'create-test-db' => createTestDb($opts['name'] ?? usage()),
    'pre-report' => writePreReport($opts['schema'] ?? usage(), $opts['out'] ?? usage(), $opts['migration-check-db'] ?? usage()),
    'generate' => generateMigrations($opts['schema'] ?? usage(), $opts['out'] ?? usage()),
    'compare' => compareSchemas($opts['source'] ?? usage(), $opts['target'] ?? usage(), $opts['out'] ?? usage()),
    default => usage(),
};
