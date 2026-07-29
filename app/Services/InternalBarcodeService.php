<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImei;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InternalBarcodeService
{
    /**
     * Barcode sản phẩm thường bắt đầu bằng 28.
     */
    public const PRODUCT_BARCODE_PREFIX = '28';

    /**
     * Barcode thiết bị IMEI bắt đầu bằng 29.
     */
    public const IMEI_BARCODE_PREFIX = '29';

    /**
     * Tổng độ dài barcode.
     */
    public const NUMERIC_BARCODE_LENGTH = 13;

    /**
     * Số chữ số dùng để chứa ID.
     *
     * 2 chữ số tiền tố + 11 chữ số ID = 13 chữ số.
     */
    public const BARCODE_ID_LENGTH = 11;

    /**
     * Giới hạn ký tự dùng để kiểm tra khả năng tương thích Code128.
     */
    public const PRODUCT_BARCODE_LIMIT = 50;

    /**
     * Tạo barcode số cho thiết bị IMEI.
     *
     * Ví dụ ID 125:
     * 2900000000125
     */
    public function generate(ProductImei $productImei): string
    {
        if (! $productImei->getKey()) {
            throw new RuntimeException(
                'Cannot generate barcode for an unsaved product IMEI.'
            );
        }

        return $this->formatNumericBarcode(
            self::IMEI_BARCODE_PREFIX,
            (int) $productImei->getKey()
        );
    }

    /**
     * Lấy hoặc tự tạo barcode số cho sản phẩm thường.
     */
    public function resolveProductBarcode(Product $product): string
    {
        if (! $product->getKey()) {
            throw new RuntimeException(
                'Cannot resolve barcode for an unsaved product.'
            );
        }

        $currentBarcode = trim((string) ($product->barcode ?? ''));

        /*
         * Chỉ giữ lại barcode hiện tại khi:
         * - Có đúng 13 chữ số.
         * - Không bị trùng với sản phẩm hoặc IMEI khác.
         *
         * Barcode dạng SPOS..., SP-... sẽ không được giữ lại.
         */
        if ($this->productBarcodeCanBeUsed($product, $currentBarcode)) {
            return $currentBarcode;
        }

        $barcode = $this->generateProductBarcode($product);

        if (! $this->productBarcodeCanBeUsed($product, $barcode)) {
            throw new RuntimeException(
                "Barcode sản phẩm {$barcode} đã tồn tại hoặc bị trùng dữ liệu."
            );
        }

        if (Schema::hasColumn('products', 'barcode')) {
            $product->forceFill([
                'barcode' => $barcode,
            ])->save();
        }

        return $barcode;
    }

    /**
     * Tạo barcode số cho sản phẩm thường.
     *
     * Ví dụ Product ID 125:
     * 2800000000125
     */
    public function generateProductBarcode(Product $product): string
    {
        if (! $product->getKey()) {
            throw new RuntimeException(
                'Cannot generate barcode for an unsaved product.'
            );
        }

        return $this->formatNumericBarcode(
            self::PRODUCT_BARCODE_PREFIX,
            (int) $product->getKey()
        );
    }

    /**
     * Kiểm tra mã sản phẩm có thể được dùng làm barcode hay không.
     *
     * Chỉ cho phép khi product.code có đúng 13 chữ số.
     * Hàm này được giữ lại để tránh ảnh hưởng code cũ đang gọi tới nó.
     */
    public function productCodeCanBeBarcode(Product $product): bool
    {
        $code = trim((string) $product->code);

        if (! $this->isNumericBarcode($code)) {
            return false;
        }

        $duplicateProductExists = Product::query()
            ->whereKeyNot($product->getKey())
            ->where(function ($query) use ($code): void {
                $query->where('code', $code);

                if (Schema::hasColumn('products', 'barcode')) {
                    $query->orWhere('barcode', $code);
                }
            })
            ->exists();

        if ($duplicateProductExists) {
            return false;
        }

        return ! ProductImei::query()
            ->withTrashed()
            ->where('barcode', $code)
            ->exists();
    }

    /**
     * Kiểm tra barcode sản phẩm có hợp lệ và không bị trùng hay không.
     */
    public function productBarcodeCanBeUsed(
        Product $product,
        ?string $barcode
    ): bool {
        $barcode = trim((string) $barcode);

        /*
         * Bắt buộc barcode sản phẩm là chuỗi gồm đúng 13 chữ số.
         */
        if (! $this->isNumericBarcode($barcode)) {
            return false;
        }

        $duplicateProductExists = Product::query()
            ->whereKeyNot($product->getKey())
            ->where(function ($query) use ($barcode): void {
                /*
                 * Kiểm tra trùng với mã sản phẩm.
                 */
                $query->where('code', $barcode);

                /*
                 * Kiểm tra trùng với barcode của sản phẩm khác.
                 */
                if (Schema::hasColumn('products', 'barcode')) {
                    $query->orWhere('barcode', $barcode);
                }
            })
            ->exists();

        if ($duplicateProductExists) {
            return false;
        }

        /*
         * Không cho barcode sản phẩm thường trùng barcode thiết bị IMEI.
         */
        return ! ProductImei::query()
            ->withTrashed()
            ->where('barcode', $barcode)
            ->exists();
    }

    /**
     * Kiểm tra chuỗi có phải barcode số gồm đúng 13 chữ số hay không.
     */
    public function isNumericBarcode(?string $value): bool
    {
        $value = trim((string) $value);

        return preg_match('/^\d{13}$/D', $value) === 1;
    }

    /**
     * Giữ lại hàm kiểm tra Code128 để tránh ảnh hưởng code cũ.
     */
    public function isCode128Compatible(?string $value): bool
    {
        $value = trim((string) $value);

        return preg_match(
            '/^[\x20-\x7E]{1,' . self::PRODUCT_BARCODE_LIMIT . '}$/D',
            $value
        ) === 1;
    }

    /**
     * Định dạng barcode số:
     *
     * Prefix 2 chữ số + ID được thêm số 0 bên trái thành 11 chữ số.
     */
    private function formatNumericBarcode(string $prefix, int $id): string
    {
        if (! preg_match('/^\d{2}$/D', $prefix)) {
            throw new RuntimeException(
                'Barcode prefix must contain exactly 2 digits.'
            );
        }

        if ($id <= 0) {
            throw new RuntimeException(
                'Barcode ID must be greater than zero.'
            );
        }

        if (strlen((string) $id) > self::BARCODE_ID_LENGTH) {
            throw new RuntimeException(
                'Barcode ID exceeds the supported length.'
            );
        }

        $barcode = $prefix . str_pad(
            (string) $id,
            self::BARCODE_ID_LENGTH,
            '0',
            STR_PAD_LEFT
        );

        if (! $this->isNumericBarcode($barcode)) {
            throw new RuntimeException(
                'Generated barcode is invalid.'
            );
        }

        return $barcode;
    }
}
