<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('brands', 'branch_id')) {
            return;
        }

        if (! Schema::hasColumn('products', 'branch_id')) {
            throw new RuntimeException(
                'Brand isolation requires products.branch_id to be migrated first.'
            );
        }

        $duplicateName = DB::table('brands')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->value('name');
        if ($duplicateName !== null) {
            throw new RuntimeException(
                'Cannot isolate legacy Brands while duplicate global names exist. '
                .'Resolve the duplicate name first: '.$duplicateName
            );
        }

        $branchIds = DB::table('branches')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if (DB::table('brands')->exists() && $branchIds === []) {
            throw new RuntimeException(
                'Cannot isolate legacy Brands because no Branch exists.'
            );
        }

        Schema::table('brands', function (Blueprint $table): void {
            $table->unsignedBigInteger('branch_id')
                ->nullable()
                ->after('id')
                ->index('brands_branch_id_index');
        });

        $brands = DB::table('brands')->orderBy('id')->get();
        foreach ($brands as $brand) {
            $referencedBranchIds = DB::table('products')
                ->where('brands_id', $brand->id)
                ->whereNotNull('branch_id')
                ->distinct()
                ->orderBy('branch_id')
                ->pluck('branch_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $targetBranchIds = $referencedBranchIds !== []
                ? $referencedBranchIds
                : $branchIds;
            $brandIdsByBranch = [];

            foreach ($targetBranchIds as $index => $branchId) {
                if ($index === 0) {
                    DB::table('brands')
                        ->where('id', $brand->id)
                        ->update(['branch_id' => $branchId]);
                    $branchBrandId = (int) $brand->id;
                } else {
                    $copy = (array) $brand;
                    unset($copy['id'], $copy['branch_id']);
                    $copy['branch_id'] = $branchId;
                    $branchBrandId = (int) DB::table('brands')->insertGetId($copy);
                }

                $brandIdsByBranch[$branchId] = $branchBrandId;
            }

            foreach ($brandIdsByBranch as $branchId => $branchBrandId) {
                DB::table('products')
                    ->where('brands_id', $brand->id)
                    ->where('branch_id', $branchId)
                    ->update(['brands_id' => $branchBrandId]);
            }
        }

        $crossBranchProducts = DB::table('products as p')
            ->join('brands as b', 'b.id', '=', 'p.brands_id')
            ->whereColumn('p.branch_id', '<>', 'b.branch_id')
            ->exists();
        if ($crossBranchProducts || DB::table('brands')->whereNull('branch_id')->exists()) {
            throw new RuntimeException(
                'Legacy Brand isolation could not prove all Product-Brand ownership.'
            );
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE brands MODIFY branch_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('brands', function (Blueprint $table): void {
                $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            });
        }

        Schema::table('brands', function (Blueprint $table): void {
            $table->foreign('branch_id', 'brands_branch_id_foreign')
                ->references('id')
                ->on('branches')
                ->restrictOnDelete();
            $table->unique(['branch_id', 'name'], 'brands_branch_name_unique');
        });
    }

    public function down(): void
    {
        // Intentionally forward-only: merging independent Branch Brands on rollback
        // would silently destroy ownership and cannot be done safely.
    }
};
