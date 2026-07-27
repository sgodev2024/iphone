<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function createPosOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $storageId = $this->resolveStorageId($user);
            $items = $this->normalizeItems($data['items']);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Giỏ hàng đang trống.',
                ]);
            }

            $imeiIds = $items
                ->where('tracking_type', Product::INVENTORY_TRACKING_IMEI)
                ->pluck('product_imei_id')
                ->sort()
                ->values();

            $imeis = $this->lockedImeis($imeiIds);
            $productIds = $items
                ->where('tracking_type', Product::INVENTORY_TRACKING_QUANTITY)
                ->pluck('product_id')
                ->merge($imeis->pluck('product_id'))
                ->unique()
                ->sort()
                ->values();

            $products = Product::query()
                ->whereIn('id', $productIds->all())
                ->where(function ($query): void {
                    $query->where('status', true)
                        ->orWhere('status', 1)
                        ->orWhere('status', '1')
                        ->orWhere('status', 'published');
                })
                ->get()
                ->keyBy('id');

            $stocks = ProductStorage::query()
                ->where('storage_id', $storageId)
                ->whereIn('product_id', $productIds->all())
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $existingImeiOrderDetails = $imeiIds->isEmpty()
                ? collect()
                : OrderDetail::query()
                    ->whereIn('product_imei_id', $imeiIds->all())
                    ->pluck('product_imei_id')
                    ->map(fn($id) => (int) $id)
                    ->flip();

            $orderItems = collect();
            $requiredByProduct = collect();
            $subtotal = 0.0;

            foreach ($items as $item) {
                $orderItem = $item['tracking_type'] === Product::INVENTORY_TRACKING_IMEI
                    ? $this->prepareImeiOrderItem(
                        $item,
                        $imeis,
                        $products,
                        $storageId,
                        $existingImeiOrderDetails
                    )
                    : $this->prepareQuantityOrderItem($item, $products);

                $productId = (int) $orderItem['product']->id;
                $quantity = (int) $orderItem['quantity'];

                $requiredByProduct[$productId] = (int) ($requiredByProduct[$productId] ?? 0) + $quantity;
                $subtotal += (float) $orderItem['total'];
                $orderItems->push($orderItem);
            }

            $this->ensureStockIsAvailable($requiredByProduct, $stocks, $products);

            $discount = $this->calculateDiscount(
                $subtotal,
                $data['discountType'] ?? null,
                (float) ($data['discountInput'] ?? 0)
            );

            $grand = max(0, $subtotal - $discount);

            if (! $this->matchesMoney($subtotal, (float) $data['subtotal'])
                || ! $this->matchesMoney($grand, (float) $data['grand'])
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Dữ liệu đơn hàng không hợp lệ, vui lòng tải lại giỏ hàng.',
                ]);
            }

            $order = Order::create([
                'client_id' => $data['customer']['id'] ?? null,
                'user_id' => $this->resolveOrderOwnerId($user),
                'code' => generateCode('orders', 'ODR'),
                'name' => $data['customer']['name'],
                'email' => $data['customer']['email'] ?? null,
                'phone' => $data['customer']['phone'],
                'address' => $data['customer']['address'] ?? null,
                'payment_method' => $data['customer']['payment'],
                'note' => $data['customer']['note'] ?? null,
                'discount_value' => $discount,
                'discount_type' => $data['discountType'] ?? null,
                'total_money' => $grand,
                'status' => 1,
                'created_by' => $user->id,
            ]);

            foreach ($orderItems as $item) {
                $order->orderDetails()->create([
                    'product_id' => $item['product']->id,
                    'product_imei_id' => $item['product_imei_id'],
                    'storage_id' => $storageId,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);

                if ($item['tracking_type'] === Product::INVENTORY_TRACKING_IMEI) {
                    $item['imei']->forceFill([
                        'status' => ProductImei::STATUS_SOLD,
                    ])->save();
                }
            }

            foreach ($requiredByProduct as $productId => $quantity) {
                $stock = $stocks->get((int) $productId);

                $updatedRows = ProductStorage::query()
                    ->whereKey($stock->id)
                    ->where('quantity', '>=', (int) $quantity)
                    ->decrement('quantity', (int) $quantity);

                if ($updatedRows !== 1) {
                    $product = $products->get((int) $productId);
                    $productName = $product?->name ?? "#{$productId}";

                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm {$productName} không đủ tồn kho để hoàn tất đơn hàng.",
                    ]);
                }
            }

            foreach ($productIds as $productId) {
                $this->syncProductTotalQuantity((int) $productId);
            }

            $this->createAccountingEntries($order, $data['customer']['payment'], $grand);

            return $order;
        }, 3);
    }

    private function resolveStorageId(User $user): int
    {
        if (! $user->storage_id) {
            throw ValidationException::withMessages([
                'storage_id' => 'Nhân viên chưa được gán kho bán hàng.',
            ]);
        }

        return (int) $user->storage_id;
    }

    private function resolveOrderOwnerId(User $user): int
    {
        if ((int) $user->role_id === 3 && $user->manager_id) {
            return (int) $user->manager_id;
        }

        return (int) $user->id;
    }

    private function normalizeItems(array $items): Collection
    {
        $normalized = collect();
        $quantityByProduct = [];
        $seenImeiIds = [];

        foreach ($items as $item) {
            $trackingType = $item['tracking_type'] ?? null;
            $productImeiId = (int) ($item['product_imei_id'] ?? 0);

            if ($trackingType === Product::INVENTORY_TRACKING_IMEI || $productImeiId > 0) {
                if ($productImeiId <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Dữ liệu IMEI trong giỏ hàng không hợp lệ.',
                    ]);
                }

                if (isset($seenImeiIds[$productImeiId])) {
                    throw ValidationException::withMessages([
                        'items' => 'Thiết bị đã có trong giỏ.',
                    ]);
                }

                $seenImeiIds[$productImeiId] = true;
                $normalized->push([
                    'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
                    'product_imei_id' => $productImeiId,
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'quantity' => 1,
                ]);

                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $quantityByProduct[$productId] = (int) ($quantityByProduct[$productId] ?? 0) + $quantity;
        }

        foreach ($quantityByProduct as $productId => $quantity) {
            $normalized->push([
                'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
                'product_id' => (int) $productId,
                'product_imei_id' => null,
                'quantity' => (int) $quantity,
            ]);
        }

        return $normalized;
    }

    private function lockedImeis(Collection $imeiIds): Collection
    {
        if ($imeiIds->isEmpty()) {
            return collect();
        }

        $imeis = ProductImei::query()
            ->with(['product', 'importDetail.import'])
            ->whereIn('id', $imeiIds->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($imeis->count() !== $imeiIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Dữ liệu đã thay đổi trong lúc thanh toán, vui lòng quét lại.',
            ]);
        }

        return $imeis;
    }

    private function prepareImeiOrderItem(
        array $item,
        Collection $imeis,
        Collection $products,
        int $storageId,
        Collection $existingImeiOrderDetails
    ): array {
        $imei = $imeis->get((int) $item['product_imei_id']);
        $product = $imei?->product;

        if (! $imei || ! $product) {
            throw ValidationException::withMessages([
                'items' => 'Dữ liệu đã thay đổi trong lúc thanh toán, vui lòng quét lại.',
            ]);
        }

        $activeProduct = $products->get((int) $product->id);

        if (! $activeProduct) {
            throw ValidationException::withMessages([
                'items' => "Sản phẩm {$product->name} không tồn tại hoặc chưa được bán.",
            ]);
        }

        if (! $activeProduct->isImeiTracked()) {
            throw ValidationException::withMessages([
                'items' => "Sản phẩm {$activeProduct->name} không phải sản phẩm quản lý IMEI.",
            ]);
        }

        if ($imei->status === ProductImei::STATUS_SOLD) {
            throw ValidationException::withMessages([
                'items' => 'Thiết bị đã bán.',
            ]);
        }

        if ($imei->status !== ProductImei::STATUS_IN_STOCK) {
            throw ValidationException::withMessages([
                'items' => 'Thiết bị đang không ở trạng thái có thể bán.',
            ]);
        }

        if ($this->resolveImeiStorageId($imei) !== $storageId) {
            throw ValidationException::withMessages([
                'items' => 'Thiết bị không thuộc kho hiện tại.',
            ]);
        }

        if ($existingImeiOrderDetails->has((int) $imei->id)) {
            throw ValidationException::withMessages([
                'items' => 'Thiết bị đang được gắn với đơn hàng khác.',
            ]);
        }

        $price = (float) $activeProduct->price_buy;

        return [
            'tracking_type' => Product::INVENTORY_TRACKING_IMEI,
            'product' => $activeProduct,
            'imei' => $imei,
            'product_imei_id' => (int) $imei->id,
            'quantity' => 1,
            'price' => $price,
            'total' => $price,
        ];
    }

    private function prepareQuantityOrderItem(
        array $item,
        Collection $products
    ): array {
        $product = $products->get((int) $item['product_id']);

        if (! $product) {
            throw ValidationException::withMessages([
                'items' => "Sản phẩm #{$item['product_id']} không tồn tại hoặc chưa được bán.",
            ]);
        }

        if (! $product->isQuantityTracked()) {
            throw ValidationException::withMessages([
                'items' => "Sản phẩm {$product->name} quản lý theo IMEI, vui lòng quét barcode thiết bị.",
            ]);
        }

        $quantity = (int) $item['quantity'];
        $price = (float) $product->price_buy;

        return [
            'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
            'product' => $product,
            'imei' => null,
            'product_imei_id' => null,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $price * $quantity,
        ];
    }

    private function ensureStockIsAvailable(
        Collection $requiredByProduct,
        Collection $stocks,
        Collection $products
    ): void {
        foreach ($requiredByProduct as $productId => $quantity) {
            $product = $products->get((int) $productId);
            $productName = $product?->name ?? "#{$productId}";
            $stock = $stocks->get((int) $productId);

            if (! $stock) {
                throw ValidationException::withMessages([
                    'items' => "Sản phẩm {$productName} không có trong kho đang bán.",
                ]);
            }

            if ((int) $stock->quantity < (int) $quantity) {
                throw ValidationException::withMessages([
                    'items' => "Sản phẩm {$productName} chỉ còn {$stock->quantity} sản phẩm trong kho, không đủ bán {$quantity} sản phẩm.",
                ]);
            }
        }
    }

    private function resolveImeiStorageId(ProductImei $imei): ?int
    {
        $storageId = $imei->importDetail?->import?->storage_id;

        return $storageId ? (int) $storageId : null;
    }

    private function calculateDiscount(float $subtotal, ?string $discountType, float $discountInput): float
    {
        if ($discountInput <= 0 || ! $discountType) {
            return 0;
        }

        if ($discountType === 'percent') {
            return $subtotal * ($discountInput / 100);
        }

        if ($discountType === 'amount') {
            return min($discountInput, $subtotal);
        }

        return 0;
    }

    private function matchesMoney(float $expected, float $actual): bool
    {
        return abs($expected - $actual) < 0.01;
    }

    private function syncProductTotalQuantity(int $productId): void
    {
        DB::statement(
            'UPDATE products SET quantity = (SELECT COALESCE(SUM(quantity), 0) FROM product_storage WHERE product_id = ?), updated_at = ? WHERE id = ?',
            [$productId, now()->toDateTimeString(), $productId]
        );
    }

    private function createAccountingEntries(Order $order, string $paymentMethod, float $grand): void
    {
        if (! in_array($paymentMethod, ['cash', 'bank_transfer', 'debt'], true)) {
            return;
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'transaction_date' => now(),
            'description' => "Bán hàng đơn {$order->code}",
            'type' => 'income',
            'document_type' => 'order',
            'reference_number' => $order->code,
            'created_by' => Auth::id(),
        ]);

        if (in_array($paymentMethod, ['cash', 'bank_transfer'], true)) {
            $moneyAccountCode = $paymentMethod === 'cash' ? 'TMCH' : 'tech';
            $moneyAccountId = Account::where('code', $moneyAccountCode)->value('id');
            $receivableAccountId = Account::where('code', '131')->value('id');

            if (! $moneyAccountId) {
                throw new \Exception("Không tìm thấy tài khoản {$moneyAccountCode}");
            }

            if (! $receivableAccountId) {
                throw new \Exception('Không tìm thấy tài khoản 131');
            }

            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $moneyAccountId,
                'debit_amount' => $grand,
                'credit_amount' => 0,
            ]);

            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $receivableAccountId,
                'debit_amount' => 0,
                'credit_amount' => $grand,
                'tableable_type' => 'App\\Models\\Client',
                'tableable_id' => $order->client_id,
            ]);

            return;
        }

        $receivableAccountId = Account::where('code', '131')->value('id');
        $revenueAccountId = Account::where('code', '5111')->value('id');

        if (! $receivableAccountId || ! $revenueAccountId) {
            throw new \Exception('Không tìm thấy tài khoản 131 hoặc 511');
        }

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $receivableAccountId,
            'debit_amount' => 0,
            'credit_amount' => $grand,
        ]);

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $revenueAccountId,
            'debit_amount' => $grand,
            'credit_amount' => 0,
            'tableable_type' => 'App\\Models\\Client',
            'tableable_id' => $order->client_id,
        ]);
    }
}
