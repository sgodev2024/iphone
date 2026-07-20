<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `check_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `difference` int NOT NULL,
  `gia_chenh_lech` int DEFAULT NULL,
  `check_inventory_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `check_detail_product_id_foreign` (`product_id`),
  KEY `check_detail_check_inventory_id_foreign` (`check_inventory_id`),
  CONSTRAINT `check_detail_check_inventory_id_foreign` FOREIGN KEY (`check_inventory_id`) REFERENCES `check_inventory` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_detail_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('check_detail');
    }
};
