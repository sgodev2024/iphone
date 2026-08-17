<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Client;
use App\Models\CustomerDebtCollection;
use App\Models\CustomerDebtCollectionAllocation;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DecimalAmount;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CustomerDebtCollectionService
{
    public function collect(User $actor, array $data): array
    {
        $ownerId = (int) $actor->ownerId();
        $payload = $this->normalizedPayload($data);
        $idempotencyKey = strtolower(trim((string) ($data['idempotency_key'] ?? '')));

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $idempotencyKey)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Idempotency key phải là UUID hợp lệ.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $actor,
                $ownerId,
                $payload,
                $idempotencyKey
            ): array {
                // Lock the business aggregate first. Every collection for this Client
                // is serialized before any remaining balance is read.
                $client = Client::withTrashed()
                    ->whereKey($payload['client_id'])
                    ->where('user_id', $ownerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = CustomerDebtCollection::query()
                    ->where('owner_id', $ownerId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return $this->replayResult(
                        $existing,
                        $this->payloadHash($this->payloadForReplay($payload, $existing))
                    );
                }

                $moneyAccount = $this->resolveMoneyAccount(
                    $payload['payment_method'],
                    $payload['requested_money_account_id']
                );
                $payload['money_account_id'] = (int) $moneyAccount->id;
                unset($payload['requested_money_account_id']);
                $payloadHash = $this->payloadHash($payload);

                $ledger = $this->canonicalLedger($ownerId, $client, true);
                $this->assertReconciled($ledger);
                $this->validateCollectibleAmount($payload['amount'], $ledger['collectible_total']);

                if ($payload['expected_first_order_id'] !== null) {
                    $firstItem = $ledger['outstanding']->first();

                    if (! $firstItem
                        || (int) $firstItem['order']->id !== $payload['expected_first_order_id']
                    ) {
                        throw ValidationException::withMessages([
                            'order_id' => 'Luồng thu theo order chỉ được thu đơn cũ nhất theo FIFO. Vui lòng dùng thu tổng theo khách hàng.',
                        ]);
                    }

                    if (DecimalAmount::compare($payload['amount'], $firstItem['remaining']) > 0) {
                        throw ValidationException::withMessages([
                            'amount' => 'Số tiền thu theo order không được vượt công nợ còn lại của đơn FIFO đầu tiên.',
                        ]);
                    }
                }

                $eligibleItems = $ledger['outstanding']->filter(
                    fn (array $item): bool => $item['sale_date'] <= $payload['collection_date']
                )->values();
                $eligibleTotal = $this->sumItems($eligibleItems, 'remaining');

                if (DecimalAmount::compare($payload['amount'], $eligibleTotal) > 0) {
                    throw ValidationException::withMessages([
                        'collection_date' => 'Ngày thu đứng trước ngày bán của một hoặc nhiều đơn cần phân bổ FIFO.',
                    ]);
                }

                $collection = CustomerDebtCollection::create([
                    'owner_id' => $ownerId,
                    'client_id' => (int) $client->id,
                    'collection_number' => $this->nextCollectionNumber($ownerId),
                    'collection_date' => $payload['collection_date'],
                    'payment_method' => $payload['payment_method'],
                    'money_account_id' => (int) $moneyAccount->id,
                    'total_amount' => $payload['amount'],
                    'note' => $payload['note'],
                    'attachment' => $payload['attachment'],
                    'status' => CustomerDebtCollection::STATUS_PENDING,
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_hash' => $payloadHash,
                    'created_by' => (int) $actor->id,
                ]);

                $unallocated = $payload['amount'];
                $allocationTotal = '0.00';
                $sequence = 0;
                $allocatedOrderIds = [];

                foreach ($eligibleItems as $item) {
                    if (DecimalAmount::isZero($unallocated)) {
                        break;
                    }

                    $sequence++;
                    $allocated = DecimalAmount::compare($unallocated, $item['remaining']) >= 0
                        ? $item['remaining']
                        : $unallocated;
                    $remainingAfter = DecimalAmount::subtract($item['remaining'], $allocated);
                    $transaction = $this->createPaymentTransaction(
                        $collection,
                        $item,
                        $allocated,
                        $sequence,
                        $moneyAccount,
                        $actor,
                        $ownerId
                    );
                    $allocation = CustomerDebtCollectionAllocation::create([
                        'collection_id' => (int) $collection->id,
                        'order_id' => (int) $item['order']->id,
                        'allocated_amount' => $allocated,
                        'allocation_sequence' => $sequence,
                        'remaining_after' => $remainingAfter,
                        'payment_transaction_id' => (int) $transaction->id,
                    ]);

                    $this->afterAllocationCreated($allocation, $sequence);
                    $allocationTotal = DecimalAmount::add($allocationTotal, $allocated);
                    $unallocated = DecimalAmount::subtract($unallocated, $allocated);
                    $allocatedOrderIds[] = (int) $item['order']->id;
                }

                if (! DecimalAmount::isZero($unallocated)
                    || DecimalAmount::compare($allocationTotal, $payload['amount']) !== 0
                ) {
                    throw new \LogicException('FIFO allocation did not consume the exact collection amount.');
                }

                $ledgerAfter = $this->canonicalLedger($ownerId, $client, false);
                $this->assertReconciled($ledgerAfter);
                $this->syncOrderAggregates($ledgerAfter, $allocatedOrderIds);

                $collection->forceFill(['status' => CustomerDebtCollection::STATUS_COMPLETED])->save();

                return $this->result($collection, $ledgerAfter, false);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = CustomerDebtCollection::query()
                ->where('owner_id', $ownerId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $existing) {
                throw $exception;
            }

            return $this->replayResult(
                $existing,
                $this->payloadHash($this->payloadForReplay($payload, $existing))
            );
        }
    }

    public function preview(User $actor, int $clientId, string|int|null $amount = null, ?string $date = null): array
    {
        $ownerId = (int) $actor->ownerId();
        $client = Client::withTrashed()
            ->whereKey($clientId)
            ->where('user_id', $ownerId)
            ->firstOrFail();
        $ledger = $this->canonicalLedger($ownerId, $client, false);
        $this->assertReconciled($ledger);
        $collectionDate = $date ? $this->normalizeCollectionDate($date) : now()->toDateString();
        $previewAmount = $amount === null ? null : $this->normalizeAmount($amount);
        $eligibleItems = $ledger['outstanding']->filter(
            fn (array $item): bool => $item['sale_date'] <= $collectionDate
        )->values();
        $eligibleTotal = $this->sumItems($eligibleItems, 'remaining');
        $allocations = collect();

        if ($previewAmount !== null) {
            $this->validateCollectibleAmount($previewAmount, $eligibleTotal);
            $unallocated = $previewAmount;

            foreach ($eligibleItems as $item) {
                if (DecimalAmount::isZero($unallocated)) {
                    break;
                }

                $allocated = DecimalAmount::compare($unallocated, $item['remaining']) >= 0
                    ? $item['remaining']
                    : $unallocated;
                $allocations->push([
                    'order_id' => (int) $item['order']->id,
                    'allocated_amount' => $allocated,
                    'remaining_after' => DecimalAmount::subtract($item['remaining'], $allocated),
                ]);
                $unallocated = DecimalAmount::subtract($unallocated, $allocated);
            }

            if (! DecimalAmount::isZero($unallocated)) {
                throw ValidationException::withMessages([
                    'collection_date' => 'Ngày thu đứng trước ngày bán của một hoặc nhiều đơn cần phân bổ FIFO.',
                ]);
            }
        }

        return [
            'client_id' => (int) $client->id,
            'status' => DecimalAmount::compare($eligibleTotal, '0.00') > 0 ? 'ready' : 'blocked',
            'can_collect' => DecimalAmount::compare($eligibleTotal, '0.00') > 0,
            'blocked_reason' => DecimalAmount::compare($eligibleTotal, '0.00') > 0
                ? null
                : ($ledger['outstanding']->isEmpty()
                    ? 'Khách hàng hiện không còn công nợ phải thu.'
                    : 'Không có công nợ đủ điều kiện tại ngày thu đã chọn.'),
            'collection_date' => $collectionDate,
            'collectible_total' => $eligibleTotal,
            'client_tk131_net' => $ledger['client_tk131_net'],
            'items' => $eligibleItems->map(fn (array $item): array => $this->itemResult($item))->values(),
            'preview_allocations' => $allocations,
        ];
    }

    public function bankAccounts(): Collection
    {
        $parent = $this->resolveRequiredActiveAccount('112', 'tài khoản ngân hàng');

        return Account::query()
            ->where('parent_id', $parent->id)
            ->where('status', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function normalizedPayload(array $data): array
    {
        $paymentMethod = strtolower(trim((string) ($data['payment_method'] ?? '')));

        if (! in_array($paymentMethod, [Order::PAYMENT_METHOD_CASH, Order::PAYMENT_METHOD_BANK_TRANSFER], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Phương thức thu công nợ không hợp lệ.',
            ]);
        }

        $clientId = (int) ($data['client_id'] ?? 0);

        if ($clientId <= 0) {
            throw ValidationException::withMessages([
                'client_id' => 'Khách hàng thu công nợ không hợp lệ.',
            ]);
        }

        $note = ($note = trim((string) ($data['note'] ?? ''))) !== '' ? $note : null;

        if ($note !== null && mb_strlen($note) > 255) {
            throw ValidationException::withMessages([
                'note' => 'Ghi chú không được vượt quá 255 ký tự.',
            ]);
        }

        return [
            'client_id' => $clientId,
            'amount' => $this->normalizeAmount($data['amount'] ?? ''),
            'collection_date' => $this->normalizeCollectionDate((string) ($data['collection_date'] ?? '')),
            'payment_method' => $paymentMethod,
            'requested_money_account_id' => $paymentMethod === Order::PAYMENT_METHOD_BANK_TRANSFER
                ? (int) ($data['money_account_id'] ?? $data['bank_account_id'] ?? 0)
                : null,
            'note' => $note,
            'attachment' => isset($data['attachment']) && is_string($data['attachment'])
                ? $data['attachment']
                : null,
            'expected_first_order_id' => isset($data['expected_first_order_id'])
                ? (int) $data['expected_first_order_id']
                : null,
        ];
    }

    private function normalizeAmount(mixed $amount): string
    {
        try {
            $normalized = DecimalAmount::normalize((string) $amount);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền thu phải là số thập phân hợp lệ với tối đa 2 chữ số lẻ.',
            ]);
        }

        if (! str_ends_with($normalized, '.00')) {
            throw ValidationException::withMessages([
                'amount' => 'Hệ thống hiện chỉ hỗ trợ số tiền nguyên VND.',
            ]);
        }

        if (! preg_match('/^-?\d{1,13}\.\d{2}$/', $normalized)) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền thu vượt giới hạn lưu trữ của ledger hiện tại.',
            ]);
        }

        return $normalized;
    }

    private function normalizeCollectionDate(string $date): string
    {
        try {
            $normalized = Carbon::createFromFormat('!Y-m-d', $date)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'collection_date' => 'Ngày thu không đúng định dạng Y-m-d.',
            ]);
        }

        if ($normalized !== $date) {
            throw ValidationException::withMessages([
                'collection_date' => 'Ngày thu không đúng định dạng Y-m-d.',
            ]);
        }

        if ($normalized > now()->toDateString()) {
            throw ValidationException::withMessages([
                'collection_date' => 'Ngày thu không được lớn hơn ngày hiện tại.',
            ]);
        }

        return $normalized;
    }

    private function canonicalLedger(int $ownerId, Client $client, bool $lockOrders): array
    {
        $accounts = $this->canonicalAccounts();
        $ordersQuery = Order::query()
            ->where('user_id', $ownerId)
            ->where('client_id', $client->id)
            ->orderBy('id');

        if ($lockOrders) {
            $ordersQuery->lockForUpdate();
        }

        $orders = $ordersQuery->get();
        $references = $orders->map(fn (Order $order): string => (string) $order->id)->all();
        $transactions = empty($references)
            ? collect()
            : Transaction::query()
                ->with('entries')
                ->where('user_id', $ownerId)
                ->where('document_type', 'order')
                ->whereIn('reference_number', $references)
                ->whereIn('type', ['sale', 'income', 'credit_notice'])
                ->get();
        $transactionsByReference = $transactions->groupBy(fn (Transaction $transaction): string => (string) $transaction->reference_number);
        $orderLedgers = collect();

        foreach ($orders as $order) {
            $orderTransactions = $transactionsByReference->get((string) $order->id, collect());
            $sale = $this->validatedSale($order, $client, $orderTransactions, $accounts);

            if (! $sale) {
                continue;
            }

            $paid = '0.00';

            foreach ($orderTransactions as $payment) {
                if (! in_array($payment->type, ['income', 'credit_notice'], true)
                    || $payment->status !== Transaction::STATUS_COMPLETED
                ) {
                    continue;
                }

                $canonicalCredit = $this->canonicalPaymentCredit($payment, $client, $accounts);

                if ($canonicalCredit !== null) {
                    $paid = DecimalAmount::add($paid, $canonicalCredit);
                }
            }

            $remaining = DecimalAmount::subtract($sale['amount'], $paid);

            if (DecimalAmount::compare($remaining, '0.00') < 0) {
                throw ValidationException::withMessages([
                    'reconciliation' => "Đơn hàng #{$order->id} đang bị thu vượt giá trị bán hàng trên ledger.",
                ]);
            }

            $orderLedgers->push([
                'order' => $order,
                'sale_transaction' => $sale['transaction'],
                'sale_date' => $sale['date'],
                'total' => $sale['amount'],
                'paid' => $paid,
                'remaining' => $remaining,
            ]);
        }

        $outstanding = $orderLedgers
            ->filter(fn (array $item): bool => DecimalAmount::compare($item['remaining'], '0.00') > 0)
            ->sort(fn (array $left, array $right): int => [$left['sale_date'], (int) $left['order']->id]
                <=> [$right['sale_date'], (int) $right['order']->id])
            ->values();
        $clientNet = $this->clientTk131Net($ownerId, $client, (int) $accounts['receivable']->id);

        return [
            'orders' => $orderLedgers->keyBy(fn (array $item): int => (int) $item['order']->id),
            'outstanding' => $outstanding,
            'collectible_total' => $this->sumItems($outstanding, 'remaining'),
            'client_tk131_net' => $clientNet,
        ];
    }

    private function canonicalAccounts(): array
    {
        $receivable = $this->resolveRequiredActiveAccount('131', 'tài khoản phải thu khách hàng');
        $revenue = $this->resolveRequiredActiveAccount('5111', 'tài khoản doanh thu bán hàng');
        $cash = $this->resolveRequiredActiveAccount('111', 'tài khoản tiền mặt');
        $bankParent = $this->resolveRequiredActiveAccount('112', 'tài khoản ngân hàng');
        $bankIds = Account::query()
            ->where('parent_id', $bankParent->id)
            ->where('status', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return compact('receivable', 'revenue', 'cash', 'bankParent', 'bankIds');
    }

    private function validatedSale(
        Order $order,
        Client $client,
        Collection $transactions,
        array $accounts
    ): ?array {
        $sales = $transactions->where('type', 'sale')->values();

        if ($sales->count() !== 1) {
            return null;
        }

        /** @var Transaction $sale */
        $sale = $sales->first();

        if ($sale->status !== Transaction::STATUS_COMPLETED || ! $sale->transaction_date) {
            return null;
        }

        $entries = $sale->entries;
        $total = DecimalAmount::normalize((string) (int) $order->total_money);
        $debit = $this->sumColumn($entries, 'debit_amount');
        $credit = $this->sumColumn($entries, 'credit_amount');
        $receivableEntries = $entries->filter(fn ($entry): bool => (int) $entry->account_id === (int) $accounts['receivable']->id
            && $entry->tableable_type === Client::class
            && (int) $entry->tableable_id === (int) $client->id
        );
        $revenueEntries = $entries->filter(fn ($entry): bool => (int) $entry->account_id === (int) $accounts['revenue']->id
        );

        if ($entries->count() !== 2
            || $receivableEntries->count() !== 1
            || $revenueEntries->count() !== 1
            || DecimalAmount::compare($debit, $credit) !== 0
            || DecimalAmount::compare($debit, $total) !== 0
            || DecimalAmount::compare($this->sumColumn($receivableEntries, 'debit_amount'), $total) !== 0
            || ! DecimalAmount::isZero($this->sumColumn($receivableEntries, 'credit_amount'))
            || DecimalAmount::compare($this->sumColumn($revenueEntries, 'credit_amount'), $total) !== 0
            || ! DecimalAmount::isZero($this->sumColumn($revenueEntries, 'debit_amount'))
        ) {
            return null;
        }

        return [
            'transaction' => $sale,
            'date' => $sale->transaction_date->toDateString(),
            'amount' => $total,
        ];
    }

    private function canonicalPaymentCredit(
        Transaction $payment,
        Client $client,
        array $accounts
    ): ?string {
        $entries = $payment->entries;

        if ($entries->count() !== 2) {
            return null;
        }

        $receivableEntries = $entries->filter(fn ($entry): bool => (int) $entry->account_id === (int) $accounts['receivable']->id
            && $entry->tableable_type === Client::class
            && (int) $entry->tableable_id === (int) $client->id
        );

        if ($receivableEntries->count() !== 1) {
            return null;
        }

        $receivableEntry = $receivableEntries->first();
        $moneyEntry = $entries->first(fn ($entry): bool => (int) $entry->id !== (int) $receivableEntry->id);
        $isCanonicalMoney = $payment->type === 'income'
            ? (int) $moneyEntry->account_id === (int) $accounts['cash']->id
            : in_array((int) $moneyEntry->account_id, $accounts['bankIds'], true);
        $debit = $this->sumColumn($entries, 'debit_amount');
        $credit = $this->sumColumn($entries, 'credit_amount');
        $receivableCredit = DecimalAmount::normalize((string) $receivableEntry->credit_amount);

        if (! $isCanonicalMoney
            || DecimalAmount::compare($receivableCredit, '0.00') <= 0
            || ! DecimalAmount::isZero((string) $receivableEntry->debit_amount)
            || ! DecimalAmount::isZero((string) $moneyEntry->credit_amount)
            || DecimalAmount::compare((string) $moneyEntry->debit_amount, $receivableCredit) !== 0
            || DecimalAmount::compare($debit, $credit) !== 0
            || DecimalAmount::compare($debit, $receivableCredit) !== 0
        ) {
            return null;
        }

        return $receivableCredit;
    }

    private function clientTk131Net(int $ownerId, Client $client, int $receivableAccountId): string
    {
        $row = DB::table('transaction_entries as te')
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('t.user_id', $ownerId)
            ->where('t.status', Transaction::STATUS_COMPLETED)
            ->where('te.account_id', $receivableAccountId)
            ->where('te.tableable_type', Client::class)
            ->where('te.tableable_id', $client->id)
            ->selectRaw('COALESCE(SUM(te.debit_amount), 0) AS debit_total, COALESCE(SUM(te.credit_amount), 0) AS credit_total')
            ->first();

        return DecimalAmount::subtract(
            (string) ($row->debit_total ?? '0.00'),
            (string) ($row->credit_total ?? '0.00')
        );
    }

    private function assertReconciled(array $ledger): void
    {
        if (DecimalAmount::compare($ledger['client_tk131_net'], '0.00') < 0) {
            throw ValidationException::withMessages([
                'reconciliation' => 'CUSTOMER_DEBT_CREDIT_BALANCE: Khách hàng đang có số dư Có TK131.',
            ]);
        }

        if (DecimalAmount::compare($ledger['client_tk131_net'], $ledger['collectible_total']) !== 0) {
            $difference = DecimalAmount::subtract(
                $ledger['client_tk131_net'],
                $ledger['collectible_total']
            );

            throw ValidationException::withMessages([
                'reconciliation' => 'CUSTOMER_DEBT_RECONCILIATION_MISMATCH: '
                    ."TK131 net {$ledger['client_tk131_net']} không khớp tổng nợ order "
                    ."{$ledger['collectible_total']} (chênh lệch {$difference}).",
            ]);
        }
    }

    private function validateCollectibleAmount(string $amount, string $collectibleTotal): void
    {
        if (DecimalAmount::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền thu phải lớn hơn 0.',
            ]);
        }

        if (DecimalAmount::compare($collectibleTotal, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Khách hàng không có công nợ order có thể thu.',
            ]);
        }

        if (DecimalAmount::compare($amount, $collectibleTotal) > 0) {
            $formattedCollectibleTotal = number_format($this->wholeAmount($collectibleTotal), 0, ',', '.');

            throw ValidationException::withMessages([
                'amount' => "Số tiền thu không được vượt quá tổng công nợ {$formattedCollectibleTotal} đ.",
            ]);
        }
    }

    private function resolveMoneyAccount(string $paymentMethod, ?int $requestedAccountId): Account
    {
        if ($paymentMethod === Order::PAYMENT_METHOD_CASH) {
            return $this->resolveRequiredActiveAccount('111', 'tài khoản tiền mặt');
        }

        $bankParent = $this->resolveRequiredActiveAccount('112', 'tài khoản ngân hàng');
        $account = Account::query()
            ->whereKey($requestedAccountId)
            ->where('parent_id', $bankParent->id)
            ->where('status', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'money_account_id' => 'Tài khoản ngân hàng phải là tài khoản đang hoạt động trực tiếp dưới 112.',
            ]);
        }

        return $account;
    }

    private function resolveRequiredActiveAccount(string $code, string $label): Account
    {
        $account = Account::query()->where('code', $code)->where('status', true)->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'account' => "Không tìm thấy {$label} ({$code}) đang hoạt động.",
            ]);
        }

        return $account;
    }

    private function createPaymentTransaction(
        CustomerDebtCollection $collection,
        array $item,
        string $amount,
        int $sequence,
        Account $moneyAccount,
        User $actor,
        int $ownerId
    ): Transaction {
        $order = $item['order'];
        $receivable = $this->resolveRequiredActiveAccount('131', 'tài khoản phải thu khách hàng');
        $isBank = $collection->payment_method === Order::PAYMENT_METHOD_BANK_TRANSFER;
        $note = $isBank ? 'Chuyển khoản' : 'Tiền mặt';
        $transaction = Transaction::create([
            'user_id' => $ownerId,
            'transaction_date' => $collection->collection_date->toDateString(),
            'description' => "Thu công nợ {$collection->collection_number} cho đơn #{$order->id}",
            'type' => $isBank ? 'credit_notice' : 'income',
            'document_type' => 'order',
            'reference_number' => (string) $order->id,
            'created_by' => (int) $actor->id,
            'status' => Transaction::STATUS_COMPLETED,
            'idempotency_key' => $this->childIdempotencyKey(
                $ownerId,
                (string) $collection->idempotency_key,
                (int) $order->id,
                $sequence
            ),
            'idempotency_hash' => hash('sha256', json_encode([
                'collection_id' => (int) $collection->id,
                'order_id' => (int) $order->id,
                'sequence' => $sequence,
                'amount' => $amount,
            ], JSON_UNESCAPED_SLASHES)),
            'collection_id' => (int) $collection->id,
        ]);

        $transaction->entries()->create([
            'account_id' => (int) $moneyAccount->id,
            'debit_amount' => $amount,
            'credit_amount' => '0.00',
            'note' => $note,
        ]);
        $transaction->entries()->create([
            'account_id' => (int) $receivable->id,
            'debit_amount' => '0.00',
            'credit_amount' => $amount,
            'tableable_type' => Client::class,
            'tableable_id' => (int) $collection->client_id,
            'note' => $note,
        ]);

        return $transaction->fresh('entries');
    }

    private function childIdempotencyKey(int $ownerId, string $collectionKey, int $orderId, int $sequence): string
    {
        $hex = hash('sha256', "customer-debt|{$ownerId}|{$collectionKey}|{$orderId}|{$sequence}");
        $hex[12] = '5';
        $hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8);

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'
            .substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }

    private function nextCollectionNumber(int $ownerId): string
    {
        User::query()->whereKey($ownerId)->lockForUpdate()->firstOrFail();
        $sequence = CustomerDebtCollection::query()
            ->where('owner_id', $ownerId)
            ->pluck('collection_number')
            ->reduce(function (int $maximum, string $number): int {
                return preg_match('/^PTCN-(\d+)$/', $number, $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0) + 1;

        return 'PTCN-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function syncOrderAggregates(array $ledger, array $orderIds): void
    {
        foreach (array_unique($orderIds) as $orderId) {
            $item = $ledger['orders']->get((int) $orderId);

            if (! $item) {
                throw new \LogicException("Allocated order #{$orderId} disappeared from canonical ledger.");
            }

            $paid = $this->wholeAmount($item['paid']);
            $remaining = $this->wholeAmount($item['remaining']);
            $status = match (true) {
                $remaining === 0 => Order::PAYMENT_STATUS_PAID,
                $paid > 0 => Order::PAYMENT_STATUS_PARTIAL,
                default => Order::PAYMENT_STATUS_DEBT,
            };

            $item['order']->forceFill([
                'paid_amount' => $paid,
                'debt_amount' => $remaining,
                'payment_status' => $status,
            ])->save();
        }
    }

    private function replayResult(CustomerDebtCollection $collection, string $payloadHash): array
    {
        if (! hash_equals((string) $collection->idempotency_hash, $payloadHash)
            || $collection->status !== CustomerDebtCollection::STATUS_COMPLETED
        ) {
            throw new ConflictHttpException('Idempotency key đã được dùng với payload khác hoặc collection chưa hoàn tất.');
        }

        $client = Client::withTrashed()->findOrFail($collection->client_id);
        $ledger = $this->canonicalLedger((int) $collection->owner_id, $client, false);

        return $this->result($collection, $ledger, true);
    }

    private function result(CustomerDebtCollection $collection, array $ledger, bool $replayed): array
    {
        return [
            'collection' => $collection->fresh([
                'allocations.order',
                'allocations.paymentTransaction.entries',
                'moneyAccount',
            ]),
            'collectible_total' => $ledger['collectible_total'],
            'replayed' => $replayed,
        ];
    }

    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode([
            'client_id' => $payload['client_id'],
            'amount' => $payload['amount'],
            'collection_date' => $payload['collection_date'],
            'payment_method' => $payload['payment_method'],
            'money_account_id' => $payload['money_account_id'],
            'note' => $payload['note'],
        ], JSON_UNESCAPED_SLASHES));
    }

    private function payloadForReplay(array $payload, CustomerDebtCollection $existing): array
    {
        $payload['money_account_id'] = $payload['payment_method'] === Order::PAYMENT_METHOD_CASH
            ? (int) $existing->money_account_id
            : (int) $payload['requested_money_account_id'];
        unset($payload['requested_money_account_id']);

        return $payload;
    }

    private function itemResult(array $item): array
    {
        return [
            'id' => (int) $item['order']->id,
            'code' => $item['order']->code,
            'sale_date' => $item['sale_date'],
            'total' => $item['total'],
            'paid' => $item['paid'],
            'remaining' => $item['remaining'],
        ];
    }

    private function sumItems(Collection $items, string $field): string
    {
        $total = '0.00';

        foreach ($items as $item) {
            $total = DecimalAmount::add($total, $item[$field]);
        }

        return $total;
    }

    private function sumColumn(Collection $entries, string $column): string
    {
        $total = '0.00';

        foreach ($entries as $entry) {
            $total = DecimalAmount::add($total, (string) $entry->{$column});
        }

        return $total;
    }

    private function wholeAmount(string $amount): int
    {
        $normalized = DecimalAmount::normalize($amount);

        if (! str_ends_with($normalized, '.00')) {
            throw new \LogicException('Order aggregates only support whole VND amounts.');
        }

        return (int) substr($normalized, 0, -3);
    }

    /**
     * Test seam for proving that a late allocation failure rolls back the entire collection.
     */
    protected function afterAllocationCreated(
        CustomerDebtCollectionAllocation $allocation,
        int $sequence
    ): void {}
}
