<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImei;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InternalBarcodeService
{
    public const PRODUCT_BARCODE_PREFIX = 'SP';

    public const PRODUCT_BARCODE_LIMIT = 50;

    public function generate(ProductImei $productImei): string
    {
        return sprintf('TEL-%08d', $productImei->id);
    }

    public function resolveProductBarcode(Product $product): string
    {
        if ($this->productCodeCanBeBarcode($product)) {
            return trim((string) $product->code);
        }

        $currentBarcode = trim((string) ($product->barcode ?? ''));

        if ($this->productBarcodeCanBeUsed($product, $currentBarcode)) {
            return $currentBarcode;
        }

        $barcode = $this->generateProductBarcode($product);

        if (Schema::hasColumn('products', 'barcode')) {
            $product->forceFill([
                'barcode' => $barcode,
            ])->save();
        }

        return $barcode;
    }

    public function generateProductBarcode(Product $product): string
    {
        if (! $product->getKey()) {
            throw new RuntimeException('Cannot generate barcode for an unsaved product.');
        }

        return sprintf('%s-%08d', self::PRODUCT_BARCODE_PREFIX, (int) $product->getKey());
    }

    public function productCodeCanBeBarcode(Product $product): bool
    {
        $code = trim((string) $product->code);

        if (! $this->isCode128Compatible($code)) {
            return false;
        }

        $duplicateCodeExists = Product::query()
            ->where('code', $code)
            ->whereKeyNot($product->getKey())
            ->exists();

        if ($duplicateCodeExists) {
            return false;
        }

        if (
            Schema::hasColumn('products', 'barcode')
            && Product::query()
                ->where('barcode', $code)
                ->whereKeyNot($product->getKey())
                ->exists()
        ) {
            return false;
        }

        return ! ProductImei::query()
            ->withTrashed()
            ->where('barcode', $code)
            ->exists();
    }

    public function productBarcodeCanBeUsed(Product $product, ?string $barcode): bool
    {
        $barcode = trim((string) $barcode);

        if (! $this->isCode128Compatible($barcode)) {
            return false;
        }

        $productQuery = Product::query()
            ->whereKeyNot($product->getKey())
            ->where('code', $barcode);

        if (Schema::hasColumn('products', 'barcode')) {
            $productQuery->orWhere(function ($query) use ($product, $barcode): void {
                $query
                    ->whereKeyNot($product->getKey())
                    ->where('barcode', $barcode);
            });
        }

        if ($productQuery->exists()) {
            return false;
        }

        return ! ProductImei::query()
            ->withTrashed()
            ->where('barcode', $barcode)
            ->exists();
    }

    public function isCode128Compatible(?string $value): bool
    {
        $value = trim((string) $value);

        return preg_match(
            '/^[\x20-\x7E]{1,' . self::PRODUCT_BARCODE_LIMIT . '}$/D',
            $value
        ) === 1;
    }
}
