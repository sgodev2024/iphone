<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\ImportCoupon;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DecimalAmount;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SupplierPaymentService
{
    public function pay(User $actor, array $data): array
    {
        $ownerId = (int) $actor->ownerId();
        $payload = $this->normalizedPayload($data);
        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
        $idempotencyKey = strtolower(trim((string) $data['idempotency_key']));

        try {
            return DB::transaction(function () use (
                $actor,
                $ownerId,
                $payload,
                $payloadHash,
                $idempotencyKey
            ): array {
                $importCoupon = ImportCoupon::query()
                    ->whereKey($payload['import_id'])
                    ->where('user_id', $ownerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = Transaction::query()
                    ->where('user_id', $ownerId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                $ledger = $this->validatedLedger($importCoupon, $ownerId);

                if ($existing) {
                    return $this->replayResult(
                        $existing,
                        $payloadHash,
                        $importCoupon,
                        $ledger,
                        $idempotencyKey
                    );
                }

                $this->validatePaymentDate($ledger['purchase'], $payload['transaction_date']);

                if (DecimalAmount::compare($payload['amount'], '0.00') <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Số tiền thanh toán phải lớn hơn 0.',
                    ]);
                }

                if (DecimalAmount::compare($ledger['remaining'], '0.00') <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Phiếu nhập đã được thanh toán đủ.',
                    ]);
                }

                if (DecimalAmount::compare($payload['amount'], $ledger['remaining']) > 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Số tiền thanh toán không được lớn hơn công nợ còn lại của phiếu nhập.',
                    ]);
                }

                $payableAccount = $this->resolveRequiredActiveAccount('331', 'tài khoản phải trả nhà cung cấp');
                $moneyAccount = $payload['payment_method'] === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
                    ? $this->resolveBankAccount($payload['bank_account_id'])
                    : $this->resolveRequiredActiveAccount('111', 'tài khoản tiền mặt');
                $reference = $this->paymentReference((int) $importCoupon->id, $idempotencyKey);
                $transaction = Transaction::create([
                    'user_id' => $ownerId,
                    'transaction_date' => $payload['transaction_date'],
                    'description' => "Thanh toán NCC cho phiếu nhập #{$importCoupon->id}",
                    'type' => 'expense',
                    'document_type' => 'import_payment',
                    'reference_number' => $reference,
                    'created_by' => (int) $actor->id,
                    'status' => Transaction::STATUS_COMPLETED,
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_hash' => $payloadHash,
                ]);

                $transaction->entries()->create([
                    'account_id' => $payableAccount->id,
                    'debit_amount' => $payload['amount'],
                    'credit_amount' => '0.00',
                    'tableable_type' => Company::class,
                    'tableable_id' => (int) $importCoupon->companies_id,
                    'note' => 'Giảm công nợ nhà cung cấp theo phiếu nhập',
                ]);
                $transaction->entries()->create([
                    'account_id' => $moneyAccount->id,
                    'debit_amount' => '0.00',
                    'credit_amount' => $payload['amount'],
                    'note' => $payload['payment_method'] === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
                        ? 'Chi chuyển khoản thanh toán nhà cung cấp'
                        : 'Chi tiền mặt thanh toán nhà cung cấp',
                ]);

                $ledgerAfter = $this->validatedLedger($importCoupon, $ownerId);
                $this->syncImportAggregates($importCoupon, $ledgerAfter);

                return $this->result($transaction, $importCoupon->fresh(), $ledgerAfter, false);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = Transaction::query()
                ->where('user_id', $ownerId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $existing) {
                throw $exception;
            }

            $importCoupon = ImportCoupon::query()
                ->whereKey($payload['import_id'])
                ->where('user_id', $ownerId)
                ->firstOrFail();
            $ledger = $this->validatedLedger($importCoupon, $ownerId);

            return $this->replayResult(
                $existing,
                $payloadHash,
                $importCoupon,
                $ledger,
                $idempotencyKey
            );
        }
    }

    public function summary(User $actor, int $importCouponId): array
    {
        $ownerId = (int) $actor->ownerId();
        $importCoupon = ImportCoupon::query()
            ->whereKey($importCouponId)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        return $this->summaryResult($this->validatedLedger($importCoupon, $ownerId));
    }

    public function bankAccounts(): Collection
    {
        $parent = $this->resolveRequiredActiveAccount('112', 'tài khoản ngân hàng');

        return Account::query()
            ->where('parent_id', $parent->id)
            ->where('status', true)
            ->where('is_default', false)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function normalizedPayload(array $data): array
    {
        $paymentMethod = strtolower(trim((string) $data['payment_method']));

        if (! in_array($paymentMethod, [
            ImportCoupon::PAYMENT_METHOD_CASH,
            ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER,
        ], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Phương thức thanh toán nhà cung cấp không hợp lệ.',
            ]);
        }

        return [
            'import_id' => (int) $data['import_coupon_id'],
            'amount' => DecimalAmount::normalize((string) $data['amount']),
            'payment_method' => $paymentMethod,
            'bank_account_id' => $paymentMethod === ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER
                ? (int) ($data['bank_account_id'] ?? 0)
                : null,
            'transaction_date' => Carbon::createFromFormat('Y-m-d', (string) $data['transaction_date'])
                ->format('Y-m-d'),
        ];
    }

    private function validatedLedger(ImportCoupon $importCoupon, int $ownerId): array
    {
        $company = Company::query()
            ->whereKey((int) $importCoupon->companies_id)
            ->where('user_id', $ownerId)
            ->first();

        if (! $company) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Nhà cung cấp của phiếu nhập không thuộc phạm vi chủ sở hữu hiện tại.',
            ]);
        }

        $goodsAccount = $this->resolveAccount('156', 'tài khoản hàng hóa');
        $payableAccount = $this->resolveAccount('331', 'tài khoản phải trả nhà cung cấp');
        $purchaseReference = $this->purchaseReference((int) $importCoupon->id);
        $purchases = Transaction::query()
            ->where('user_id', $ownerId)
            ->where('type', 'expense')
            ->where('document_type', 'import')
            ->where('reference_number', $purchaseReference)
            ->get();

        if ($purchases->count() !== 1) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Phiếu nhập phải có đúng một bút toán mua hàng canonical trước khi thanh toán.',
            ]);
        }

        $purchase = $purchases->first();

        if ($purchase->status !== Transaction::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Bút toán mua hàng của phiếu nhập chưa ở trạng thái completed.',
            ]);
        }

        $purchaseEntries = $this->entriesFor($purchase);
        $purchaseTotal = DecimalAmount::normalize((string) (int) $importCoupon->total);
        $purchaseDebit = $this->sumColumn($purchaseEntries, 'debit_amount');
        $purchaseCredit = $this->sumColumn($purchaseEntries, 'credit_amount');
        $goodsDebit = $this->sumMatching(
            $purchaseEntries,
            fn ($entry) => (int) $entry->account_id === (int) $goodsAccount->id,
            'debit_amount'
        );
        $companyPayableCredit = $this->sumMatching(
            $purchaseEntries,
            fn ($entry) => (int) $entry->account_id === (int) $payableAccount->id
                && $entry->tableable_type === Company::class
                && (int) $entry->tableable_id === (int) $company->id,
            'credit_amount'
        );

        if ($purchaseEntries->count() !== 2
            || DecimalAmount::compare($purchaseDebit, $purchaseCredit) !== 0
            || DecimalAmount::compare($purchaseCredit, $purchaseTotal) !== 0
            || DecimalAmount::compare($goodsDebit, $purchaseTotal) !== 0
            || DecimalAmount::compare($companyPayableCredit, $purchaseTotal) !== 0
        ) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Bút toán mua hàng không cân bằng hoặc không khớp Dr156/Cr331, tổng tiền và Company.',
            ]);
        }

        $paymentPrefix = $this->paymentPrefix((int) $importCoupon->id);
        $payments = Transaction::query()
            ->where('user_id', $ownerId)
            ->where('type', 'expense')
            ->where('document_type', 'import_payment')
            ->where('reference_number', 'like', $paymentPrefix.'%')
            ->orderBy('id')
            ->get();
        $paid = '0.00';

        foreach ($payments as $payment) {
            $paid = DecimalAmount::add(
                $paid,
                $this->validatedPaymentDebit($payment, $company, $payableAccount)
            );
        }

        $remaining = DecimalAmount::subtract($purchaseTotal, $paid);

        if (DecimalAmount::compare($remaining, '0.00') < 0) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Ledger TK331 của phiếu nhập đang bị thanh toán vượt quá giá trị mua hàng.',
            ]);
        }

        return [
            'purchase' => $purchase,
            'purchase_credit' => $purchaseTotal,
            'payments' => $payments,
            'paid' => $paid,
            'remaining' => $remaining,
        ];
    }

    private function validatedPaymentDebit(
        Transaction $payment,
        Company $company,
        Account $payableAccount
    ): string {
        if ($payment->status !== Transaction::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Phiếu nhập có bút toán thanh toán chưa completed.',
            ]);
        }

        $entries = $this->entriesFor($payment);
        $debit = $this->sumColumn($entries, 'debit_amount');
        $credit = $this->sumColumn($entries, 'credit_amount');
        $payableDebit = $this->sumMatching(
            $entries,
            fn ($entry) => (int) $entry->account_id === (int) $payableAccount->id
                && $entry->tableable_type === Company::class
                && (int) $entry->tableable_id === (int) $company->id,
            'debit_amount'
        );
        $moneyEntry = $entries->first(function ($entry) use ($payableAccount): bool {
            return (int) $entry->account_id !== (int) $payableAccount->id
                && DecimalAmount::compare($entry->credit_amount, '0.00') > 0;
        });

        if (! $moneyEntry || ! $this->isCanonicalMoneyAccount((int) $moneyEntry->account_id)) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Phiếu nhập có bút toán thanh toán không dùng đúng TK111 hoặc tài khoản con của 112.',
            ]);
        }

        if ($entries->count() !== 2
            || DecimalAmount::compare($debit, $credit) !== 0
            || DecimalAmount::compare($debit, $payableDebit) !== 0
            || DecimalAmount::compare($payableDebit, '0.00') <= 0
            || DecimalAmount::compare($moneyEntry->credit_amount, $payableDebit) !== 0
        ) {
            throw ValidationException::withMessages([
                'import_coupon_id' => 'Bút toán thanh toán nhà cung cấp không cân bằng hoặc không khớp Company/TK331.',
            ]);
        }

        return $payableDebit;
    }

    private function validatePaymentDate(Transaction $purchase, string $transactionDate): void
    {
        $purchaseDate = $purchase->transaction_date?->toDateString();
        $today = now()->toDateString();

        if (($purchaseDate && $transactionDate < $purchaseDate) || $transactionDate > $today) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Ngày trả phải từ ngày mua hàng đến ngày hiện tại.',
            ]);
        }
    }

    private function resolveBankAccount(?int $bankAccountId): Account
    {
        $parent = $this->resolveRequiredActiveAccount('112', 'tài khoản ngân hàng');
        $account = Account::query()
            ->whereKey($bankAccountId)
            ->where('parent_id', $parent->id)
            ->where('status', true)
            ->where('is_default', false)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Tài khoản ngân hàng phải là tài khoản con đang hoạt động trực tiếp dưới 112.',
            ]);
        }

        return $account;
    }

    private function isCanonicalMoneyAccount(int $accountId): bool
    {
        $cashId = Account::query()->where('code', '111')->value('id');

        if ($cashId && $accountId === (int) $cashId) {
            return true;
        }

        $bankParentId = Account::query()->where('code', '112')->value('id');

        return $bankParentId
            && Account::query()->whereKey($accountId)->where('parent_id', $bankParentId)->exists();
    }

    private function syncImportAggregates(ImportCoupon $importCoupon, array $ledger): void
    {
        $paid = $this->wholeAmount($ledger['paid']);
        $remaining = $this->wholeAmount($ledger['remaining']);
        $status = match (true) {
            $remaining === 0 => ImportCoupon::PAYMENT_STATUS_PAID,
            $paid > 0 => ImportCoupon::PAYMENT_STATUS_PARTIAL,
            default => ImportCoupon::PAYMENT_STATUS_DEBT,
        };

        $importCoupon->forceFill([
            'paid_amount' => $paid,
            'debt_amount' => $remaining,
            'payment_status' => $status,
        ])->save();
    }

    private function replayResult(
        Transaction $transaction,
        string $payloadHash,
        ImportCoupon $importCoupon,
        array $ledger,
        string $idempotencyKey
    ): array {
        $expectedReference = $this->paymentReference((int) $importCoupon->id, $idempotencyKey);

        if (! hash_equals((string) $transaction->idempotency_hash, $payloadHash)
            || $transaction->type !== 'expense'
            || $transaction->document_type !== 'import_payment'
            || $transaction->reference_number !== $expectedReference
        ) {
            throw new ConflictHttpException('Idempotency key đã được dùng với payload khác.');
        }

        return $this->result($transaction, $importCoupon->fresh(), $ledger, true);
    }

    private function result(
        Transaction $transaction,
        ImportCoupon $importCoupon,
        array $ledger,
        bool $replayed
    ): array {
        return [
            'transaction' => $transaction->fresh('entries'),
            'import_coupon' => $importCoupon,
            'summary' => $this->summaryResult($ledger),
            'replayed' => $replayed,
        ];
    }

    private function summaryResult(array $ledger): array
    {
        return [
            'purchase_credit' => $this->wholeAmount($ledger['purchase_credit']),
            'paid_amount' => $this->wholeAmount($ledger['paid']),
            'remaining' => $this->wholeAmount($ledger['remaining']),
            'payment_count' => $ledger['payments']->count(),
            'purchase_date' => $ledger['purchase']->transaction_date?->toDateString(),
        ];
    }

    private function entriesFor(Transaction $transaction): Collection
    {
        return DB::table('transaction_entries')
            ->where('transaction_id', $transaction->id)
            ->select([
                'account_id',
                DB::raw('CAST(debit_amount AS CHAR) AS debit_amount'),
                DB::raw('CAST(credit_amount AS CHAR) AS credit_amount'),
                'tableable_type',
                'tableable_id',
            ])
            ->get()
            ->map(function ($entry) {
                $entry->debit_amount = DecimalAmount::normalize((string) $entry->debit_amount);
                $entry->credit_amount = DecimalAmount::normalize((string) $entry->credit_amount);

                return $entry;
            });
    }

    private function sumColumn(Collection $entries, string $column): string
    {
        $total = '0.00';

        foreach ($entries as $entry) {
            $total = DecimalAmount::add($total, $entry->{$column});
        }

        return $total;
    }

    private function sumMatching(Collection $entries, callable $predicate, string $column): string
    {
        return $this->sumColumn($entries->filter($predicate), $column);
    }

    private function resolveAccount(string $code, string $label): Account
    {
        $account = Account::query()->where('code', $code)->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'account' => "Không tìm thấy {$label} ({$code}).",
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

    private function wholeAmount(string $amount): int
    {
        $normalized = DecimalAmount::normalize($amount);

        if (! str_ends_with($normalized, '.00')) {
            throw ValidationException::withMessages([
                'amount' => 'Hệ thống hiện chỉ hỗ trợ số tiền nguyên VND.',
            ]);
        }

        return (int) substr($normalized, 0, -3);
    }

    private function purchaseReference(int $importCouponId): string
    {
        return 'IMP-'.$importCouponId;
    }

    private function paymentPrefix(int $importCouponId): string
    {
        return $this->purchaseReference($importCouponId).'-PAY-';
    }

    private function paymentReference(int $importCouponId, string $idempotencyKey): string
    {
        return $this->paymentPrefix($importCouponId).$idempotencyKey;
    }
}
