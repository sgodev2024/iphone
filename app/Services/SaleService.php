<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\ProductStorage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function createPosOrder(User $user, array $data, int $storageId): Order
    {
        return DB::transaction(function () use ($user, $data, $storageId) {
            $ownerId = $this->resolveOrderOwnerId($user);
            $paymentMethod = (string) ($data['payment_method']
                ?? data_get($data, 'customer.payment', Order::PAYMENT_METHOD_CASH));
            $submittedPaidAmount = array_key_exists('paid_amount', $data)
                && $data['paid_amount'] !== null
                ? (int) $data['paid_amount']
                : null;
            $cashTendered = array_key_exists('cash_tendered', $data)
                && $data['cash_tendered'] !== null
                ? (int) $data['cash_tendered']
                : null;
            $paidAmount = $paymentMethod === Order::PAYMENT_METHOD_CASH
                ? 0
                : ($submittedPaidAmount
                    ?? ($paymentMethod === Order::PAYMENT_METHOD_DEBT ? 0 : (int) ($data['grand'] ?? 0)));
            $bankAccountId = isset($data['bank_account_id'])
                ? (int) $data['bank_account_id']
                : null;
            $client = null;
            $branchId = auth()->user()->branchScopeId();
            if (! empty($data['customer']['id'])) {
                $client = Client::query()
                    ->where('user_id', $ownerId)
                    ->find($data['customer']['id']);

                if (! $client) {
                    throw ValidationException::withMessages([
                        'customer.id' => 'Khách hàng không tồn tại hoặc đã ngừng hoạt động.',
                    ]);
                }
            }

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
                    ->map(fn ($id) => (int) $id)
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

            if ($paymentMethod !== Order::PAYMENT_METHOD_CASH && $paidAmount < 0) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount must not be negative.',
                ]);
            }

            if ($paymentMethod !== Order::PAYMENT_METHOD_CASH && $paidAmount > $grand) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Số tiền khách trả không được lớn hơn tổng tiền đơn hàng.',
                ]);
            }

            if ($paymentMethod === Order::PAYMENT_METHOD_CASH) {
                if ($cashTendered === null || $cashTendered < 0) {
                    throw ValidationException::withMessages([
                        'cash_tendered' => 'Cash tendered must be a non-negative integer.',
                    ]);
                }

                $paidAmount = min($cashTendered, (int) round($grand));
            }

            $debtAmount = max(0, (int) round($grand - $paidAmount));

            if ($paidAmount === 0 && $paymentMethod !== Order::PAYMENT_METHOD_DEBT) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Đơn chưa thanh toán phải chọn phương thức công nợ.',
                ]);
            }

            if ($paidAmount > 0 && $paymentMethod === Order::PAYMENT_METHOD_DEBT) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Đơn công nợ không được ghi nhận tiền trả tại checkout.',
                ]);
            }

            if ($debtAmount > 0 && ! $client) {
                throw ValidationException::withMessages([
                    'customer.id' => 'Đơn còn công nợ bắt buộc phải chọn khách hàng.',
                ]);
            }

            if ($paymentMethod === Order::PAYMENT_METHOD_BANK_TRANSFER) {
                $this->resolveBankAccountId($bankAccountId);
            } elseif ($bankAccountId !== null) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Tài khoản ngân hàng chỉ dùng cho phương thức chuyển khoản.',
                ]);
            }

            $paymentStatus = $debtAmount <= 0
                ? Order::PAYMENT_STATUS_PAID
                : ($paidAmount <= 0 ? Order::PAYMENT_STATUS_DEBT : Order::PAYMENT_STATUS_PARTIAL);

            $orderData = [
                'client_id' => $client?->id,
                'user_id' => $ownerId,
                'branch_id' => $branchId,
                'code' => generateCode('orders', 'ODR'),
                'name' => $client?->name ?? ($data['customer']['name'] ?? null),
                'phone' => $client?->phone ?? ($data['customer']['phone'] ?? null),
                'email' => $client?->email ?? ($data['customer']['email'] ?? null),
                'address' => $client?->address ?? ($data['customer']['address'] ?? null),
                'receive_address' => $client?->address ?? ($data['customer']['address'] ?? null),
                'note' => $data['customer']['note'] ?? null,
                'total_money' => $grand,
                'discount_value' => $discount,
                'discount_type' => $data['discountType'] ?? null,
                'payment_method' => $paymentMethod,
                'paid_amount' => $paidAmount,
                'debt_amount' => $debtAmount,
                'payment_status' => $paymentStatus,
                'status' => 1,
                'created_by' => $user->id,
                'notification' => 1,
            ];

            $order = Order::create(array_intersect_key(
                $orderData,
                array_flip(Schema::getColumnListing('orders'))
            ));

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

            $this->createAccountingEntries($order, $user, $ownerId, $bankAccountId);

            return $order;
        }, 3);
    }

    private function resolveOrderOwnerId(User $user): int
    {
        return $user->ownerId();
    }

    private function normalizeItems(array $items): Collection
    {
        $normalized = collect();
        $quantityByProduct = [];
        $seenImeiIds = [];

        foreach ($items as $item) {
            $unitPrice = $this->validatedUnitPrice($item['unit_price'] ?? null);
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
                    'unit_price' => $unitPrice,
                ]);

                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            if (isset($quantityByProduct[$productId])
                && $quantityByProduct[$productId]['unit_price'] !== $unitPrice
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Một sản phẩm không thể có nhiều giá bán khác nhau trong cùng giỏ hàng.',
                ]);
            }

            $quantityByProduct[$productId] = [
                'quantity' => (int) ($quantityByProduct[$productId]['quantity'] ?? 0) + $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        foreach ($quantityByProduct as $productId => $item) {
            $normalized->push([
                'tracking_type' => Product::INVENTORY_TRACKING_QUANTITY,
                'product_id' => (int) $productId,
                'product_imei_id' => null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return $normalized;
    }

    private function validatedUnitPrice(mixed $unitPrice): int
    {
        $validatedUnitPrice = filter_var($unitPrice, FILTER_VALIDATE_INT);

        if ($validatedUnitPrice === false || $validatedUnitPrice <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Giá bán phải lớn hơn 0.',
            ]);
        }

        return $validatedUnitPrice;
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

        $price = (float) $item['unit_price'];

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
        $price = (float) $item['unit_price'];

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
            if ($discountInput > 100) {
                throw ValidationException::withMessages([
                    'discountInput' => 'Giảm giá phần trăm không được lớn hơn 100%.',
                ]);
            }

            return $subtotal * ($discountInput / 100);
        }

        if ($discountType === 'amount') {
            if ($discountInput > $subtotal) {
                throw ValidationException::withMessages([
                    'discountInput' => 'Giảm giá không được lớn hơn tạm tính.',
                ]);
            }

            return $discountInput;
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

    private function createAccountingEntries(
        Order $order,
        User $creator,
        int $ownerId,
        ?int $bankAccountId = null
    ): void {
        DB::transaction(function () use ($order, $creator, $ownerId, $bankAccountId): void {
            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $totalAmount = (float) $order->total_money;
            $paidAmount = (float) $order->paid_amount;
            $debtAmount = (float) $order->debt_amount;

            if (! $this->matchesMoney($totalAmount, $paidAmount + $debtAmount)) {
                throw new \UnexpectedValueException(
                    "Đơn hàng #{$order->id} có tổng tiền, đã trả và còn nợ không khớp."
                );
            }

            $expectedPaymentStatus = $debtAmount <= 0
                ? 'paid'
                : ($paidAmount <= 0 ? 'debt' : 'partial');

            if ($order->payment_status !== $expectedPaymentStatus) {
                throw new \UnexpectedValueException(
                    "Đơn hàng #{$order->id} có trạng thái thanh toán không khớp với số tiền đã trả và còn nợ."
                );
            }

            $receivableAccountId = $this->resolveRequiredAccount(
                '131',
                'tài khoản phải thu khách hàng'
            )->id;

            $this->createSaleTransaction(
                $order,
                $totalAmount,
                (int) $receivableAccountId,
                $creator,
                $ownerId
            );

            if ($paidAmount <= 0) {
                return;
            }

            $this->createPaymentTransaction(
                $order,
                $paidAmount,
                (int) $receivableAccountId,
                $creator,
                $ownerId,
                $bankAccountId
            );
        });
    }

    private function createSaleTransaction(
        Order $order,
        float $totalAmount,
        int $receivableAccountId,
        User $creator,
        int $ownerId
    ): void {
        if ($this->hasAccountingTransactionForOrder($order, ['sale'])) {
            return;
        }

        $revenueAccountId = $this->resolveRequiredAccount(
            '5111',
            'tài khoản doanh thu bán hàng'
        )->id;
        $transaction = Transaction::create([
            'user_id' => $ownerId,
            'transaction_date' => now()->toDateString(),
            'description' => "Bán hàng theo đơn #{$order->id}",
            'type' => 'sale',
            'document_type' => 'order',
            'reference_number' => (string) $order->id,
            'created_by' => $creator->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);

        $transaction->entries()->create([
            'account_id' => $receivableAccountId,
            'debit_amount' => $totalAmount,
            'credit_amount' => 0,
            'tableable_type' => $order->client_id ? Client::class : null,
            'tableable_id' => $order->client_id,
            'note' => 'Ghi nhận phải thu đơn hàng',
        ]);
        $transaction->entries()->create([
            'account_id' => $revenueAccountId,
            'debit_amount' => 0,
            'credit_amount' => $totalAmount,
            'note' => 'Doanh thu bán hàng',
        ]);
    }

    private function createPaymentTransaction(
        Order $order,
        float $paidAmount,
        int $receivableAccountId,
        User $creator,
        int $ownerId,
        ?int $bankAccountId
    ): void {
        // Checkout owns at most one initial payment. Later debt collections use the
        // dedicated service and always carry a non-null idempotency key.
        $checkoutPaymentExists = Transaction::query()
            ->where('user_id', $ownerId)
            ->where('document_type', 'order')
            ->where('reference_number', (string) $order->id)
            ->whereIn('type', ['income', 'credit_notice'])
            ->whereNull('idempotency_key')
            ->exists();

        if ($checkoutPaymentExists) {
            return;
        }

        $paymentMethod = (string) $order->payment_method;

        if (! in_array($paymentMethod, ['cash', 'bank_transfer'], true)) {
            throw new \UnexpectedValueException(
                "Đơn hàng #{$order->id} có số tiền đã trả nhưng phương thức thanh toán không hợp lệ."
            );
        }

        $moneyAccountId = $paymentMethod === 'bank_transfer'
            ? $this->resolveBankAccountId($bankAccountId)
            : $this->resolveCashAccountId();
        $transactionType = $paymentMethod === 'bank_transfer' ? 'credit_notice' : 'income';
        $paymentNote = $paymentMethod === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt';
        $transaction = Transaction::create([
            'user_id' => $ownerId,
            'transaction_date' => now()->toDateString(),
            'description' => "Thu tiền đơn hàng #{$order->id}",
            'type' => $transactionType,
            'document_type' => 'order',
            'reference_number' => (string) $order->id,
            'created_by' => $creator->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);

        $transaction->entries()->create([
            'account_id' => $moneyAccountId,
            'debit_amount' => $paidAmount,
            'credit_amount' => 0,
            'note' => $paymentNote,
        ]);
        $transaction->entries()->create([
            'account_id' => $receivableAccountId,
            'debit_amount' => 0,
            'credit_amount' => $paidAmount,
            'tableable_type' => $order->client_id ? Client::class : null,
            'tableable_id' => $order->client_id,
            'note' => $paymentNote,
        ]);
    }

    private function hasAccountingTransactionForOrder(Order $order, array $types): bool
    {
        return Transaction::query()
            ->where('document_type', 'order')
            ->where('reference_number', (string) $order->id)
            ->whereIn('type', $types)
            ->exists();
    }

    private function resolveCashAccountId(): int
    {
        $parent = $this->resolveRequiredAccount('111', 'tài khoản tiền mặt');

        return (int) $parent->id;
    }

    private function resolveBankAccountId(?int $bankAccountId): int
    {
        $parent = $this->resolveRequiredAccount('112', 'tài khoản ngân hàng');

        $account = Account::query()
            ->whereKey($bankAccountId)
            ->where('parent_id', $parent->id)
            ->where('status', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Tài khoản ngân hàng phải là tài khoản đang hoạt động trực tiếp dưới 112.',
            ]);
        }

        return (int) $account->id;
    }

    private function resolveRequiredAccount(string $code, string $label): Account
    {
        $account = Account::query()
            ->where('code', $code)
            ->first();

        if (! $account) {
            throw new \Exception(
                "Không tìm thấy {$label} ({$code}). "
                .'Vui lòng cấu hình tại Tài khoản kế toán (/admin/accounts) hoặc chạy AccountingAccountSeeder.'
            );
        }

        if (! (bool) $account->status) {
            throw new \Exception("{$label} ({$code}) đang bị tắt. Vui lòng bật trạng thái tài khoản kế toán.");
        }

        return $account;
    }

}
