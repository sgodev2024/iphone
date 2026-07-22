<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_imeis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->string('imei', 15)->unique();
            $table->string('status', 30)->default('in_stock');
            $table->timestamps();

            $table->index('status');
            $table->index(['product_id', 'status']);
        });

        $this->copyValidLegacyImeis();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_imeis');
    }

    private function copyValidLegacyImeis(): void
    {
        if (! Schema::hasColumn('products', 'imei')) {
            return;
        }

        DB::table('products')
            ->select(['id', 'imei'])
            ->whereNotNull('imei')
            ->where('imei', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                $now = now();
                $rows = [];

                foreach ($products as $product) {
                    $imei = trim((string) $product->imei);

                    if (preg_match('/^\d{15}$/D', $imei) !== 1) {
                        continue;
                    }

                    $rows[] = [
                        'product_id' => $product->id,
                        'imei' => $imei,
                        'status' => 'in_stock',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('product_imeis')->insertOrIgnore($rows);
                }
            });
    }
};
