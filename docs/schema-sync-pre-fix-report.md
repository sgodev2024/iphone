# Pre-fix migration/schema drift report

Generated at: 2026-07-20T11:06:32+07:00

## Scope

- Source database used as truth: `ai_crm_2026` via Laravel connection `mysql`.
- Existing migration directory: `database/migrations`.
- No destructive command was run against the source database.
- Existing migrations were tested only on a separate database: `ai_crm_2026_migcheck_20260720110428`.

## Source schema snapshot

- Tables: 55
- Columns: 451
- Index rows from `information_schema.STATISTICS`: 143
- Foreign key column rows: 34
- Rows in source `migrations` table: 103

## Tables in source database

- accounts (InnoDB, utf8mb4_unicode_ci)
- banks (InnoDB, utf8mb4_unicode_ci)
- brands (InnoDB, utf8mb4_unicode_ci)
- carts (InnoDB, utf8mb4_unicode_ci)
- categories (InnoDB, utf8mb4_unicode_ci)
- check_detail (InnoDB, utf8mb4_unicode_ci)
- check_inventory (InnoDB, utf8mb4_unicode_ci)
- city (InnoDB, utf8mb4_unicode_ci)
- client_group (InnoDB, utf8mb4_unicode_ci)
- clients (InnoDB, utf8mb4_unicode_ci)
- companies (InnoDB, utf8mb4_unicode_ci)
- company_product (InnoDB, utf8mb4_unicode_ci)
- config (InnoDB, utf8mb4_unicode_ci)
- customer_debts (InnoDB, utf8mb4_unicode_ci)
- customer_debts_detail (InnoDB, utf8mb4_unicode_ci)
- customers (InnoDB, utf8mb4_unicode_ci)
- districts (InnoDB, utf8mb4_unicode_ci)
- expense (InnoDB, utf8mb4_unicode_ci)
- expense_detail (InnoDB, utf8mb4_unicode_ci)
- fields (InnoDB, utf8mb4_unicode_ci)
- import (InnoDB, utf8mb4_unicode_ci)
- import_coupon (InnoDB, utf8mb4_unicode_ci)
- import_detail (InnoDB, utf8mb4_unicode_ci)
- jobs (InnoDB, utf8mb4_unicode_ci)
- migrations (InnoDB, utf8mb4_unicode_ci)
- order_details (InnoDB, utf8mb4_unicode_ci)
- orders (InnoDB, utf8mb4_unicode_ci)
- password_resets (InnoDB, utf8mb4_unicode_ci)
- personal_access_tokens (InnoDB, utf8mb4_unicode_ci)
- product_images (InnoDB, utf8mb4_unicode_ci)
- product_storage (InnoDB, utf8mb4_unicode_ci)
- products (InnoDB, utf8mb4_unicode_ci)
- products_code (InnoDB, utf8mb4_general_ci)
- receipts (InnoDB, utf8mb4_unicode_ci)
- receipts_detail (InnoDB, utf8mb4_unicode_ci)
- role_permission (InnoDB, utf8mb4_unicode_ci)
- roles (InnoDB, utf8mb4_unicode_ci)
- sgo_campaign_details (InnoDB, latin1_swedish_ci)
- sgo_campaigns (InnoDB, utf8mb4_unicode_ci)
- sgo_oa_template (InnoDB, utf8mb4_unicode_ci)
- sgo_zalo_oas (InnoDB, utf8mb4_unicode_ci)
- sgo_zns_messages (InnoDB, utf8mb4_unicode_ci)
- storages (InnoDB, utf8mb4_unicode_ci)
- super_admins (InnoDB, utf8mb4_unicode_ci)
- supplier_debts (InnoDB, utf8mb4_unicode_ci)
- supplier_debts_detail (InnoDB, utf8mb4_unicode_ci)
- suppliers (InnoDB, utf8mb4_unicode_ci)
- transaction_entries (InnoDB, utf8mb4_unicode_ci)
- transactions (InnoDB, utf8mb4_unicode_ci)
- user_info (InnoDB, utf8mb4_unicode_ci)
- user_wallet (InnoDB, utf8mb4_unicode_ci)
- users (InnoDB, utf8mb4_unicode_ci)
- wallets (InnoDB, utf8mb4_unicode_ci)
- wards (InnoDB, utf8mb4_unicode_ci)
- warehouse (InnoDB, utf8mb4_unicode_ci)

## Migration file/database table mismatch

- Files in `database/migrations`: 112
- Migration rows in DB: 103

Migration rows in DB without matching file:

- 2019_12_14_000001_create_personal_access_tokens_table
- 2024_07_01_154336_add_client_id_to_orders_table
- 2024_08_03_163733_create_sgo_oa_tokens_table
- 2024_08_03_163837_create_sgo_oa_details_table
- 2024_08_03_164000_create_sgo_oa_logs_table
- 2024_08_06_084735_create_messages_table
- 2024_08_07_151647_add_note_to_sgo_zns_messages
- 2024_08_07_163058_add_template_id_to_sgo_zns_messages_table

Migration files pending / not recorded in DB:

- 2024_06_27_090928_create_users_table
- 2024_06_27_091638_create_users_table
- 2024_06_27_094638_add_name_phone_to_orders_table
- 2024_07_12_083923_add_columns_to_user_table
- 2024_07_16_141237_add_column_to_product_table
- 2024_07_23_135656_add_column_to_suppliers_table
- 2024_07_30_141826_add_storage_id_to_import_coupon_table
- 2024_08_15_081015_add_dob_column_to_users_table
- 2024_08_23_103647_add_storage_id_to_order_details_table
- 2024_08_28_112009_adjust_sgo_campaigns_table
- 2024_08_28_165648_ajust_sgo_campaign_details_table
- 2024_09_05_082301_add_city_to_companies_table
- 2024_10_01_101529_add_wallet_column_to_users_table
- 2024_10_01_103719_create_sgo_transactions_table
- 2024_10_01_105844_add_columns_to_super_admins_table
- 2024_10_01_153446_add_description_to_sgo_transaction_table
- 2026_07_20_000000_drop_zalo_oa_zns_tables

## Existing migration build result on separate DB

Running current migrations on `ai_crm_2026_migcheck_20260720110428` failed before a complete schema could be built.

First failure:

```text
2024_06_25_154113_create_config_table FAIL
SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table 'banks'
SQL: alter table `config` add constraint `config_bank_id_foreign` foreign key (`bank_id`) references `banks` (`id`)
```

Root cause observed from files:

- `2024_06_25_154113_create_config_table.php` creates `config.bank_id` FK to `banks.id`.
- `2024_06_27_094831_create_banks_table.php` creates `banks`, but it is ordered later by timestamp.

Because the current migration set fails on an empty test DB, a full table/column/index/FK comparison from old migrations to the source schema cannot be completed reliably. The source schema export in `storage/app/schema-sync/source-schema.json` is therefore used as the baseline truth for the new generated migration set and final schema comparison.

## Foreign keys present in source schema

- accounts: 1 FK column(s)
- brands: 1 FK column(s)
- carts: 2 FK column(s)
- categories: 1 FK column(s)
- check_detail: 2 FK column(s)
- check_inventory: 1 FK column(s)
- clients: 1 FK column(s)
- companies: 3 FK column(s)
- company_product: 2 FK column(s)
- config: 2 FK column(s)
- customer_debts: 1 FK column(s)
- order_details: 1 FK column(s)
- orders: 3 FK column(s)
- product_storage: 1 FK column(s)
- products: 3 FK column(s)
- sgo_campaign_details: 2 FK column(s)
- sgo_campaigns: 1 FK column(s)
- storages: 1 FK column(s)
- transaction_entries: 2 FK column(s)
- transactions: 2 FK column(s)
- users: 1 FK column(s)

## Captured artifacts

- `storage/app/schema-sync/migrate-status.txt`
- `storage/app/schema-sync/migration-files.txt`
- `storage/app/schema-sync/migrations-table.txt`
- `storage/app/schema-sync/source-schema.json`
- `storage/app/schema-sync/current-migrations-run.txt`
