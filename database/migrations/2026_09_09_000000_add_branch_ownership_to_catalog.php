<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('products')->exists()
            || DB::table('categories')->exists()
            || DB::table('brands')->exists()
        ) {
            throw new \RuntimeException(
                'Branch-owned catalog migration requires an empty local/test catalog. '
                .'Run a controlled local migrate:fresh; legacy branch ownership will not be guessed.'
            );
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique('categories_name_unique');
            $table->foreignId('branch_id')
                ->after('id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->unique(['branch_id', 'name'], 'categories_branch_name_unique');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->foreignId('branch_id')
                ->after('id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->unique(['branch_id', 'name'], 'brands_branch_name_unique');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_barcode_unique');
            $table->foreignId('branch_id')
                ->after('id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->unique(['branch_id', 'code'], 'products_branch_code_unique');
            $table->unique(['branch_id', 'barcode'], 'products_branch_barcode_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `product_imeis` MODIFY `storage_id` BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `product_imeis` MODIFY `storage_id` BIGINT UNSIGNED NULL');
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_branch_code_unique');
            $table->dropUnique('products_branch_barcode_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique('barcode', 'products_barcode_unique');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->dropUnique('brands_branch_name_unique');
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique('categories_branch_name_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique('name', 'categories_name_unique');
        });
    }
};
