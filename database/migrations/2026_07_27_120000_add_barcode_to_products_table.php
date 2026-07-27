<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('barcode', 50)
                    ->nullable()
                    ->unique()
                    ->after('code');
            });
        }

        $codeCounts = DB::table('products')
            ->select('code', DB::raw('COUNT(*) as total'))
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->groupBy('code')
            ->pluck('total', 'code');

        $imeiBarcodes = Schema::hasTable('product_imeis')
            ? DB::table('product_imeis')
                ->whereNotNull('barcode')
                ->pluck('barcode')
                ->flip()
            : collect();

        DB::table('products')
            ->select(['id', 'code', 'barcode'])
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($codeCounts, $imeiBarcodes): void {
                foreach ($products as $product) {
                    $code = trim((string) $product->code);

                    if (
                        $this->isCode128Compatible($code)
                        && (int) ($codeCounts[$code] ?? 0) === 1
                        && ! $imeiBarcodes->has($code)
                    ) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'barcode' => $code,
                                'updated_at' => now(),
                            ]);

                        continue;
                    }

                    if ($this->isCode128Compatible($product->barcode ?? null)) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'barcode' => $this->productBarcode((int) $product->id),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'barcode')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });
    }

    private function productBarcode(int $productId): string
    {
        return sprintf('SP-%08d', $productId);
    }

    private function isCode128Compatible(?string $value): bool
    {
        $value = trim((string) $value);

        return preg_match('/^[\x20-\x7E]{1,50}$/D', $value) === 1;
    }
};
