# Final migration baseline report

Generated at: 2026-07-20 11:12 +07:00

## Safety result

- No destructive migration command was run against source database `ai_crm_2026`.
- Did not run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or rollback against the source database.
- Existing `database/migrations` files were not edited or deleted.
- Full backup created at `database/migrations_backup_20260720_110638`.
- New baseline migrations were generated separately at `database/migrations_baseline_20260720_110648`.

## Pre-fix drift summary

- Source database has 55 tables, 451 columns, 143 index rows, and 34 foreign-key column rows.
- `database/migrations` has 112 PHP migration files.
- Source `migrations` table has 103 rows.
- 17 migration files are pending/not recorded in the source DB.
- 8 migration rows exist in the DB but no matching file exists in `database/migrations`.
- Current migration set does not build from an empty test DB. It fails at `2024_06_25_154113_create_config_table` because it creates `config.bank_id` FK to `banks.id` before `banks` exists.
- Detailed pre-fix report: `docs/schema-sync-pre-fix-report.md`.

## Generated baseline

- Generated 54 baseline migration PHP files, one for each source table except Laravel's own `migrations` table.
- Used current MySQL `SHOW CREATE TABLE` as the source of truth.
- Removed table option `AUTO_INCREMENT=N` from generated SQL so next-id state from real data is not embedded.
- No `INSERT`, `UPDATE`, or `DELETE` data statements were generated.
- Foreign keys and indexes are included in the generated `CREATE TABLE` SQL.
- Migration order was sorted by FK dependency and verified by running on a separate test DB.

## Test database result

- Current migration test DB: `ai_crm_2026_migcheck_20260720110428`.
- Baseline migration test DB: `ai_crm_2026_baseline_20260720110949`.
- Baseline migrations ran successfully on the baseline test DB.
- Exported baseline test schema and compared against the source schema.
- Comparison result: no schema differences detected.
- Comparison artifact: `storage/app/schema-sync/baseline-schema-comparison.md`.

## Schema dump

- Ran `php artisan schema:dump`.
- Result: failed because `mysqldump` is not installed or not available in `PATH`.
- Checked common Windows install locations for MySQL, MariaDB, XAMPP, and Laragon; no `mysqldump.exe` was found.
- Because the official Laravel command failed, no trusted `database/schema/mysql-schema.sql` was produced.
- Artifact with full error: `storage/app/schema-sync/schema-dump.txt`.

## Commands run

```powershell
php artisan migrate:status
Get-ChildItem database\migrations -File
php tools/schema_sync.php export --out=storage\app\schema-sync\source-schema.json
php tools/schema_sync.php create-test-db --name=ai_crm_2026_migcheck_20260720110428
php artisan migrate --database=mysql --schema-path=storage\app\schema-sync\no-schema.sql --force
php tools/schema_sync.php pre-report --schema=storage\app\schema-sync\source-schema.json --out=docs\schema-sync-pre-fix-report.md --migration-check-db=ai_crm_2026_migcheck_20260720110428
Copy-Item -Path database\migrations -Destination database\migrations_backup_20260720_110638 -Recurse
php tools/schema_sync.php generate --schema=storage\app\schema-sync\source-schema.json --out=database\migrations_baseline_20260720_110648
php artisan schema:dump
php tools/schema_sync.php create-test-db --name=ai_crm_2026_baseline_20260720110949
php artisan migrate --database=mysql --path=D:\iphone\database\migrations_baseline_20260720_110648 --realpath --schema-path=storage\app\schema-sync\no-schema.sql --force
php tools/schema_sync.php export --out=storage\app\schema-sync\baseline-test-schema.json
php tools/schema_sync.php compare --source=storage\app\schema-sync\source-schema.json --target=storage\app\schema-sync\baseline-test-schema.json --out=storage\app\schema-sync\baseline-schema-comparison.md
php -l tools\schema_sync.php
php -l database\migrations_baseline_20260720_110648\*.php
```

## Recommendation

- Do not delete old migrations yet.
- After installing `mysqldump`, rerun `php artisan schema:dump` and review how the team wants to adopt the baseline.
- For future database changes, create new forward-only migration files after the accepted baseline.
