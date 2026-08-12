<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Client;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerSaleBackfillService
{
    public const EXPECTED_OWNER_ID = 23;

    public const EXPECTED_SALE_TOTAL = 75951110;

    public const EXPECTED_PAYMENT_TOTAL = 73951110;

    public const APPROVED_ORDER_IDS = [
        46, 47, 48, 49, 50,
        51, 52, 53, 54, 55,
        56, 57, 58, 59, 60,
    ];

    private const APPROVED_ORDERS = [
        46 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        47 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        48 => ['client_id' => 11, 'total' => 9500000, 'paid' => 9500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        49 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        50 => ['client_id' => 11, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        51 => ['client_id' => 11, 'total' => 25555555, 'paid' => 25555555, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        52 => ['client_id' => null, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        53 => ['client_id' => null, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        54 => ['client_id' => 11, 'total' => 2000000, 'paid' => 0, 'debt' => 2000000, 'method' => 'debt', 'payment_status' => 'debt'],
        55 => ['client_id' => 12, 'total' => 1500000, 'paid' => 1500000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        56 => ['client_id' => 12, 'total' => 25555555, 'paid' => 25555555, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        57 => ['client_id' => 11, 'total' => 900000, 'paid' => 900000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        58 => ['client_id' => 12, 'total' => 600000, 'paid' => 600000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        59 => ['client_id' => 11, 'total' => 540000, 'paid' => 540000, 'debt' => 0, 'method' => 'cash', 'payment_status' => 'paid'],
        60 => ['client_id' => 11, 'total' => 800000, 'paid' => 800000, 'debt' => 0, 'method' => 'bank_transfer', 'payment_status' => 'paid'],
    ];

    public function preview(array $orderIds): array
    {
        return $this->analyze($orderIds, false);
    }

    public function execute(array $orderIds): array
    {
        return DB::transaction(function () use ($orderIds): array {
            $analysis = $this->analyze($orderIds, true);
            $paymentBefore = $analysis['payment_metrics'];

            if ($analysis['state'] === 'already') {
                $analysis['execution'] = [
                    'created' => 0,
                    'transaction_ids' => [],
                    'payment_before' => $paymentBefore,
                    'payment_after' => $paymentBefore,
                ];

                return $analysis;
            }

            $created = [];
            $receivableAccount = $analysis['accounts']['131'];
            $revenueAccount = $analysis['accounts']['5111'];

            foreach ($analysis['orders'] as $order) {
                $transaction = Transaction::create([
                    'user_id' => (int) $order->user_id,
                    'transaction_date' => $order->created_at->toDateString(),
                    'description' => "Bán hàng theo đơn #{$order->id}",
                    'type' => 'sale',
                    'document_type' => 'order',
                    'reference_number' => (string) $order->id,
                    'created_by' => (int) $order->created_by,
                ]);

                $transaction->entries()->create([
                    'account_id' => $receivableAccount->id,
                    'debit_amount' => $order->total_money,
                    'credit_amount' => 0,
                    'tableable_type' => $order->client_id ? Client::class : null,
                    'tableable_id' => $order->client_id,
                    'note' => 'Ghi nhận phải thu đơn hàng',
                ]);
                $transaction->entries()->create([
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $order->total_money,
                    'note' => 'Doanh thu bán hàng',
                ]);

                $created[(int) $order->id] = (int) $transaction->id;
            }

            $postValidation = $this->analyze($orderIds, false);
            $paymentAfter = $postValidation['payment_metrics'];

            if ($postValidation['state'] !== 'already' || count($created) !== count(self::APPROVED_ORDER_IDS)) {
                throw new RuntimeException('Post-validation failed: the complete sale batch was not created.');
            }

            if ($paymentBefore !== $paymentAfter) {
                throw new RuntimeException('Post-validation failed: existing payment accounting changed.');
            }

            $postValidation['execution'] = [
                'created' => count($created),
                'transaction_ids' => $created,
                'payment_before' => $paymentBefore,
                'payment_after' => $paymentAfter,
            ];

            return $postValidation;
        }, 3);
    }

    private function analyze(array $orderIds, bool $lockOrders): array
    {
        $orderIds = $this->validateRequestedOrderIds($orderIds);
        $accounts = $this->resolveAccounts();
        $ordersQuery = Order::query()->whereIn('id', $orderIds)->orderBy('id');

        if ($lockOrders) {
            $ordersQuery->lockForUpdate();
        }

        $orders = $ordersQuery->get();
        $errors = $this->validateOrders($orders);
        $references = array_map('strval', $orderIds);
        $saleTransactions = Transaction::query()
            ->with('entries')
            ->where('document_type', 'order')
            ->whereIn('reference_number', $references)
            ->where('type', 'sale')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Transaction $transaction): string => (string) $transaction->reference_number);
        $paymentTransactions = Transaction::query()
            ->with('entries')
            ->where('document_type', 'order')
            ->whereIn('reference_number', $references)
            ->whereIn('type', ['income', 'credit_notice'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Transaction $transaction): string => (string) $transaction->reference_number);
        $rows = [];
        $saleStates = [];

        foreach ($orders as $order) {
            $orderSales = $saleTransactions->get((string) $order->id, collect());
            $orderPayments = $paymentTransactions->get((string) $order->id, collect());
            $saleState = 'missing';

            if ($orderSales->count() > 1) {
                $errors[] = "Order #{$order->id} has duplicate sale transactions.";
                $saleState = 'invalid';
            } elseif ($orderSales->count() === 1) {
                $saleErrors = $this->validateSaleTransaction(
                    $orderSales->first(),
                    $order,
                    $accounts['131'],
                    $accounts['5111']
                );

                if ($saleErrors === []) {
                    $saleState = 'valid';
                } else {
                    $saleState = 'invalid';
                    array_push($errors, ...$saleErrors);
                }
            }

            array_push(
                $errors,
                ...$this->validatePaymentTransactions($orderPayments, $order, $accounts)
            );
            $saleStates[(int) $order->id] = $saleState;
            $rows[] = [
                'order_id' => (int) $order->id,
                'owner_id' => (int) $order->user_id,
                'client_id' => $order->client_id !== null ? (int) $order->client_id : null,
                'total' => (int) $order->total_money,
                'paid' => (int) $order->paid_amount,
                'debt' => (int) $order->debt_amount,
                'sale_transaction_id' => $orderSales->count() === 1 ? (int) $orderSales->first()->id : null,
                'payment_transaction_ids' => $orderPayments->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                'sale_state' => $saleState,
                'transaction_date' => $order->created_at?->toDateString(),
            ];
        }

        $paymentMetrics = $this->paymentMetrics($paymentTransactions->flatten(1), $accounts['131']);

        if ($paymentMetrics['count'] !== 14) {
            $errors[] = "Expected 14 payment transactions, found {$paymentMetrics['count']}.";
        }

        if ($paymentMetrics['credit_131'] !== self::EXPECTED_PAYMENT_TOTAL) {
            $errors[] = 'Expected payment Credit 131 '.self::EXPECTED_PAYMENT_TOTAL
                .", found {$paymentMetrics['credit_131']}.";
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(PHP_EOL, array_unique($errors)));
        }

        $missingCount = collect($saleStates)->filter(fn (string $state): bool => $state === 'missing')->count();
        $validCount = collect($saleStates)->filter(fn (string $state): bool => $state === 'valid')->count();

        if ($missingCount === count(self::APPROVED_ORDER_IDS)) {
            $state = 'missing';
        } elseif ($validCount === count(self::APPROVED_ORDER_IDS)) {
            $state = 'already';
        } else {
            throw new RuntimeException(
                "Mixed batch state: {$validCount} already backfilled and {$missingCount} missing. Manual review required."
            );
        }

        return [
            'state' => $state,
            'orders' => $orders,
            'accounts' => $accounts,
            'rows' => $rows,
            'order_count' => $orders->count(),
            'sale_total' => (int) $orders->sum('total_money'),
            'planned_debit_131' => $state === 'missing' ? self::EXPECTED_SALE_TOTAL : 0,
            'planned_credit_5111' => $state === 'missing' ? self::EXPECTED_SALE_TOTAL : 0,
            'planned_credit_131' => 0,
            'planned_debit_111_112' => 0,
            'payment_metrics' => $paymentMetrics,
        ];
    }

    private function validateRequestedOrderIds(array $orderIds): array
    {
        $normalized = collect($orderIds)
            ->map(function ($id): int {
                if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
                    throw new RuntimeException('Every --orders value must be a positive integer.');
                }

                return (int) $id;
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($normalized !== self::APPROVED_ORDER_IDS) {
            throw new RuntimeException(
                'The requested orders must exactly match the approved Phase 3A whitelist: '
                .implode(',', self::APPROVED_ORDER_IDS).'.'
            );
        }

        return $normalized;
    }

    private function resolveAccounts(): array
    {
        $accounts = Account::query()
            ->whereIn('code', ['111', '112', '131', '5111'])
            ->get()
            ->keyBy('code');

        foreach (['111', '112', '131', '5111'] as $code) {
            $account = $accounts->get($code);

            if (! $account || ! (bool) $account->status) {
                throw new RuntimeException("Required active account {$code} was not found.");
            }
        }

        $bankAccounts = Account::query()
            ->where('parent_id', $accounts['112']->id)
            ->where('status', true)
            ->get();

        if ($bankAccounts->isEmpty()) {
            throw new RuntimeException('No active bank account exists below account 112.');
        }

        return [
            '111' => $accounts['111'],
            '112' => $accounts['112'],
            '131' => $accounts['131'],
            '5111' => $accounts['5111'],
            'bank_ids' => $bankAccounts->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        ];
    }

    private function validateOrders(Collection $orders): array
    {
        $errors = [];

        if ($orders->count() !== count(self::APPROVED_ORDER_IDS)) {
            $errors[] = 'Expected 15 approved orders, found '.$orders->count().'.';
        }

        $ordersById = $orders->keyBy('id');
        $clientIds = collect(self::APPROVED_ORDERS)->pluck('client_id')->filter()->unique()->values();
        $clients = Client::withTrashed()->whereIn('id', $clientIds)->get()->keyBy('id');

        foreach (self::APPROVED_ORDERS as $orderId => $expected) {
            $order = $ordersById->get($orderId);

            if (! $order) {
                $errors[] = "Approved order #{$orderId} does not exist.";

                continue;
            }

            $actualClientId = $order->client_id !== null ? (int) $order->client_id : null;
            $checks = [
                'user_id' => [(int) $order->user_id, self::EXPECTED_OWNER_ID],
                'client_id' => [$actualClientId, $expected['client_id']],
                'total_money' => [(int) $order->total_money, $expected['total']],
                'paid_amount' => [(int) $order->paid_amount, $expected['paid']],
                'debt_amount' => [(int) $order->debt_amount, $expected['debt']],
                'payment_method' => [(string) $order->payment_method, $expected['method']],
                'payment_status' => [(string) $order->payment_status, $expected['payment_status']],
                'status' => [(int) $order->status, 1],
                'created_by' => [(int) $order->created_by, self::EXPECTED_OWNER_ID],
            ];

            foreach ($checks as $field => [$actual, $approved]) {
                if ($actual !== $approved) {
                    $errors[] = "Order #{$orderId} {$field} changed: expected "
                        .var_export($approved, true).', found '.var_export($actual, true).'.';
                }
            }

            if (! $order->created_at) {
                $errors[] = "Order #{$orderId} has no historical created_at date.";
            }

            if ((int) $order->total_money !== (int) $order->paid_amount + (int) $order->debt_amount) {
                $errors[] = "Order #{$orderId} total_money does not equal paid_amount + debt_amount.";
            }

            if ($expected['client_id'] !== null) {
                $client = $clients->get($expected['client_id']);

                if (! $client) {
                    $errors[] = "Order #{$orderId} client #{$expected['client_id']} does not exist.";
                } elseif ((int) $client->user_id !== self::EXPECTED_OWNER_ID) {
                    $errors[] = "Order #{$orderId} client belongs to the wrong owner.";
                }
            }
        }

        if ((int) $orders->sum('total_money') !== self::EXPECTED_SALE_TOTAL) {
            $errors[] = 'Expected approved total '.self::EXPECTED_SALE_TOTAL
                .', found '.(int) $orders->sum('total_money').'.';
        }

        return $errors;
    }

    private function validateSaleTransaction(
        Transaction $transaction,
        Order $order,
        Account $receivableAccount,
        Account $revenueAccount
    ): array {
        $errors = [];
        $prefix = "Sale transaction #{$transaction->id} for order #{$order->id}";

        if ((int) $transaction->user_id !== (int) $order->user_id
            || (int) $transaction->created_by !== (int) $order->created_by
            || $transaction->transaction_date?->toDateString() !== $order->created_at?->toDateString()
            || (string) $transaction->description !== "Bán hàng theo đơn #{$order->id}"
        ) {
            $errors[] = "{$prefix} has invalid metadata.";
        }

        if ($transaction->entries->count() !== 2) {
            $errors[] = "{$prefix} must have exactly two entries.";

            return $errors;
        }

        $receivableEntries = $transaction->entries->where('account_id', $receivableAccount->id);
        $revenueEntries = $transaction->entries->where('account_id', $revenueAccount->id);
        $expectedType = $order->client_id ? Client::class : null;
        $receivable = $receivableEntries->first();
        $revenue = $revenueEntries->first();

        if ($receivableEntries->count() !== 1
            || ! $receivable
            || ! $this->amountEquals($receivable->debit_amount, $order->total_money)
            || ! $this->amountEquals($receivable->credit_amount, 0)
            || $receivable->tableable_type !== $expectedType
            || ($receivable->tableable_id !== null ? (int) $receivable->tableable_id : null)
                !== ($order->client_id !== null ? (int) $order->client_id : null)
        ) {
            $errors[] = "{$prefix} has an invalid Debit 131 entry.";
        }

        if ($revenueEntries->count() !== 1
            || ! $revenue
            || ! $this->amountEquals($revenue->debit_amount, 0)
            || ! $this->amountEquals($revenue->credit_amount, $order->total_money)
            || $revenue->tableable_type !== null
            || $revenue->tableable_id !== null
        ) {
            $errors[] = "{$prefix} has an invalid Credit 5111 entry.";
        }

        if (! $this->amountEquals($transaction->entries->sum('debit_amount'), $order->total_money)
            || ! $this->amountEquals($transaction->entries->sum('credit_amount'), $order->total_money)
        ) {
            $errors[] = "{$prefix} is not balanced.";
        }

        return $errors;
    }

    private function validatePaymentTransactions(Collection $transactions, Order $order, array $accounts): array
    {
        $errors = [];
        $paidAmount = (int) $order->paid_amount;

        if ($paidAmount === 0) {
            return $transactions->isEmpty()
                ? []
                : ["Order #{$order->id} must not have a payment transaction."];
        }

        if ($transactions->count() !== 1) {
            return ["Order #{$order->id} must have exactly one existing payment transaction."];
        }

        $transaction = $transactions->first();
        $expectedType = $order->payment_method === 'bank_transfer' ? 'credit_notice' : 'income';
        $moneyAccountIds = $order->payment_method === 'bank_transfer'
            ? $accounts['bank_ids']
            : [(int) $accounts['111']->id];

        if ($transaction->type !== $expectedType
            || (int) $transaction->user_id !== (int) $order->user_id
            || (int) $transaction->created_by !== (int) $order->created_by
        ) {
            $errors[] = "Order #{$order->id} existing payment metadata is invalid.";
        }

        if ($transaction->entries->count() !== 2) {
            $errors[] = "Order #{$order->id} existing payment must have exactly two entries.";

            return $errors;
        }

        $receivableEntries = $transaction->entries->where('account_id', $accounts['131']->id);
        $moneyEntries = $transaction->entries->filter(
            fn ($entry): bool => in_array((int) $entry->account_id, $moneyAccountIds, true)
        );
        $receivable = $receivableEntries->first();
        $money = $moneyEntries->first();
        $expectedClientType = $order->client_id ? Client::class : null;

        if ($receivableEntries->count() !== 1
            || ! $receivable
            || ! $this->amountEquals($receivable->debit_amount, 0)
            || ! $this->amountEquals($receivable->credit_amount, $paidAmount)
            || $receivable->tableable_type !== $expectedClientType
            || ($receivable->tableable_id !== null ? (int) $receivable->tableable_id : null)
                !== ($order->client_id !== null ? (int) $order->client_id : null)
        ) {
            $errors[] = "Order #{$order->id} existing payment Credit 131 is invalid.";
        }

        if ($moneyEntries->count() !== 1
            || ! $money
            || ! $this->amountEquals($money->debit_amount, $paidAmount)
            || ! $this->amountEquals($money->credit_amount, 0)
        ) {
            $errors[] = "Order #{$order->id} existing payment money debit is invalid.";
        }

        if (! $this->amountEquals($transaction->entries->sum('debit_amount'), $paidAmount)
            || ! $this->amountEquals($transaction->entries->sum('credit_amount'), $paidAmount)
        ) {
            $errors[] = "Order #{$order->id} existing payment is not balanced.";
        }

        return $errors;
    }

    private function paymentMetrics(Collection $transactions, Account $receivableAccount): array
    {
        return [
            'count' => $transactions->count(),
            'credit_131' => (int) $transactions
                ->flatMap->entries
                ->where('account_id', $receivableAccount->id)
                ->sum('credit_amount'),
        ];
    }

    private function amountEquals(mixed $actual, mixed $expected): bool
    {
        return abs((float) $actual - (float) $expected) < 0.01;
    }
}
