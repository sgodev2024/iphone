<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashVoucher;
use App\Models\User;
use App\Support\DecimalAmount;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class GenericCashVoucherService
{
    public function create(User $actor, array $data): CashVoucher
    {
        $this->rejectAuthoritativeFields($data);
        $direction = strtolower(trim((string) ($data['direction'] ?? '')));
        $operation = strtolower(trim((string) ($data['operation'] ?? '')));
        $this->validateOperationPair($direction, $operation);

        try {
            $amount = DecimalAmount::normalize((string) ($data['amount'] ?? '0'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền không hợp lệ.',
            ]);
        }

        if (DecimalAmount::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Số tiền phải lớn hơn 0.',
            ]);
        }

        $transactionDate = $this->normalizeDate((string) ($data['transaction_date'] ?? ''));
        $ownerId = (int) $actor->ownerId();
        $cashAccount = $this->resolveCanonicalCashAccount();
        $storedAttachment = $this->storeAttachment($data['attachment'] ?? null);

        try {
            return DB::transaction(function () use (
                $actor,
                $ownerId,
                $direction,
                $operation,
                $transactionDate,
                $cashAccount,
                $amount,
                $storedAttachment,
                $data
            ): CashVoucher {
                User::query()->whereKey($ownerId)->lockForUpdate()->firstOrFail();

                return CashVoucher::create([
                    'owner_id' => $ownerId,
                    'voucher_number' => $this->nextVoucherNumber($ownerId, $direction),
                    'direction' => $direction,
                    'operation' => $operation,
                    'transaction_date' => $transactionDate,
                    'cash_account_id' => (int) $cashAccount->id,
                    'amount' => $amount,
                    'document_type' => $this->nullableTrimmed($data['document_type'] ?? null),
                    'reference_number' => $this->nullableTrimmed($data['reference_number'] ?? null),
                    'description' => $this->nullableTrimmed($data['description'] ?? null),
                    'attachment' => $storedAttachment,
                    'accounting_status' => CashVoucher::STATUS_PENDING_ACCOUNTING,
                    'created_by' => (int) $actor->id,
                ]);
            }, 3)->fresh(['cashAccount', 'creator']);
        } catch (Throwable $exception) {
            if ($storedAttachment !== null) {
                Storage::disk('public')->delete($storedAttachment);
            }

            throw $exception;
        }
    }

    private function validateOperationPair(string $direction, string $operation): void
    {
        $valid = ($direction === CashVoucher::DIRECTION_RECEIPT
                && $operation === CashVoucher::OPERATION_GENERIC_RECEIPT)
            || ($direction === CashVoucher::DIRECTION_PAYMENT
                && $operation === CashVoucher::OPERATION_GENERIC_PAYMENT);

        if (! $valid) {
            throw ValidationException::withMessages([
                'operation' => 'Nghiệp vụ không phù hợp với loại giao dịch đã chọn.',
            ]);
        }
    }

    private function rejectAuthoritativeFields(array $data): void
    {
        foreach ([
            'account_id',
            'cash_account_id',
            'counter_account_id',
            'owner_id',
            'created_by',
            'accounting_status',
            'accounting_transaction_id',
            'obj_type',
            'obj_id',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                throw ValidationException::withMessages([
                    $field => 'Trường này do hệ thống quản lý và không được phép gửi lên.',
                ]);
            }
        }
    }

    private function resolveCanonicalCashAccount(): Account
    {
        $account = Account::query()
            ->where('code', '111')
            ->where('status', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'cash_account' => 'Không tìm thấy tài khoản tiền mặt 111 đang hoạt động.',
            ]);
        }

        return $account;
    }

    private function normalizeDate(string $date): string
    {
        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $date);
            $normalized = $parsed->format('Y-m-d');
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Ngày thu chi không hợp lệ.',
            ]);
        }

        if ($normalized !== $date) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Ngày thu chi không hợp lệ.',
            ]);
        }

        if ($normalized > now()->toDateString()) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Ngày thu chi không được lớn hơn ngày hiện tại.',
            ]);
        }

        return $normalized;
    }

    private function nextVoucherNumber(int $ownerId, string $direction): string
    {
        $prefix = $direction === CashVoucher::DIRECTION_RECEIPT ? 'PTTM-' : 'PCTM-';
        $sequence = CashVoucher::query()
            ->where('owner_id', $ownerId)
            ->where('direction', $direction)
            ->pluck('voucher_number')
            ->reduce(function (int $maximum, string $number) use ($prefix): int {
                $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

                return preg_match($pattern, $number, $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0) + 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function storeAttachment(mixed $attachment): ?string
    {
        if (! $attachment instanceof UploadedFile) {
            return null;
        }

        $filename = Str::uuid().'.'.$attachment->getClientOriginalExtension();

        return $attachment->storeAs('attachments/cash_vouchers', $filename, 'public');
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
