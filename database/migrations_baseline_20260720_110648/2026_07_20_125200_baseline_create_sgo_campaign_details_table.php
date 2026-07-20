<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `sgo_campaign_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `scheduled_date` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sgo_campaign_details_campaign_id_foreign` (`campaign_id`),
  KEY `sgo_campaign_details_user_id_foreign` (`user_id`),
  CONSTRAINT `sgo_campaign_details_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `sgo_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sgo_campaign_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sgo_campaign_details');
    }
};
