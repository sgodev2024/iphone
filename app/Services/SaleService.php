<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Order;
use App\Models\Product;
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

            $productIds = $items->keys()->sort()->values()->all();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('status', true)
                ->get()
                ->keyBy('id');

            $stocks = ProductStorage::query()
                ->where('storage_id', $storageId)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $orderItems = [];
            $subtotal = 0;

            foreach ($items as $productId => $quantity) {
                $product = $products->get($productId);

                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm #{$productId} không tồn tại hoặc chưa được bán.",
                    ]);
                }

                $stock = $stocks->get($productId);

                if (!$stock) {
                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm {$product->name} không có trong kho đang bán.",
                    ]);
                }

                if ((int) $stock->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm {$product->name} chỉ còn {$stock->quantity} sản phẩm trong kho, không đủ bán {$quantity} sản phẩm.",
                    ]);
                }

                $lineTotal = (float) $product->price_buy * $quantity;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product' => $product,
                    'stock' => $stock,
                    'quantity' => $quantity,
                    'price' => (float) $product->price_buy,
                    'total' => $lineTotal,
                ];
            }

            $discount = $this->calculateDiscount(
                $subtotal,
                $data['discountType'] ?? null,
                (float) ($data['discountInput'] ?? 0)
            );

            $grand = max(0, $subtotal - $discount);

            if (!$this->matchesMoney($subtotal, (float) $data['subtotal']) || !$this->matchesMoney($grand, (float) $data['grand'])) {
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
                $product = $item['product'];
                $quantity = $item['quantity'];

                $order->orderDetails()->create([
                    'product_id' => $product->id,
                    'storage_id' => $storageId,
                    'p_name' => $product->name,
                    'p_price' => $item['price'],
                    'p_quantity' => $quantity,
                ]);

                $updatedRows = ProductStorage::query()
                    ->whereKey($item['stock']->id)
                    ->where('quantity', '>=', $quantity)
                    ->decrement('quantity', $quantity);

                if ($updatedRows !== 1) {
                    throw ValidationException::withMessages([
                        'items' => "Sản phẩm {$product->name} không đủ tồn kho để hoàn tất đơn hàng.",
                    ]);
                }
            }

            foreach ($productIds as $productId) {
                $this->syncProductTotalQuantity($productId);
            }

            $this->createAccountingEntries($order, $data['customer']['payment'], $grand);

            return $order;
        }, 3);
    }

    private function resolveStorageId(User $user): int
    {
        if (!$user->storage_id) {
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
        return collect($items)
            ->groupBy(fn (array $item) => (int) $item['product_id'])
            ->map(fn (Collection $group) => $group->sum(fn (array $item) => (int) $item['quantity']))
            ->filter(fn ($quantity) => $quantity > 0);
    }

    private function calculateDiscount(float $subtotal, ?string $discountType, float $discountInput): float
    {
        if ($discountInput <= 0 || !$discountType) {
            return 0;
        }

        if ($discountType === 'percent') {
            return $subtotal * ($discountInput / 100);
        }

        if ($discountType === 'amount') {
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

    private function createAccountingEntries(Order $order, string $paymentMethod, float $grand): void
    {
        if (!in_array($paymentMethod, ['cash', 'bank_transfer', 'debt'], true)) {
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

            if (!$moneyAccountId) {
                throw new \Exception("Không tìm thấy tài khoản {$moneyAccountCode}");
            }

            if (!$receivableAccountId) {
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

        if (!$receivableAccountId || !$revenueAccountId) {
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
