<?php

namespace App\Http\Requests\Admin;

use App\Models\CashVoucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGenericCashVoucherRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'direction' => strtolower(trim((string) $this->input('direction'))),
            'operation' => strtolower(trim((string) $this->input('operation'))),
            'document_type' => $this->nullableTrimmed('document_type'),
            'reference_number' => $this->nullableTrimmed('reference_number'),
            'description' => $this->nullableTrimmed('description'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('cash_transaction.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in([
                CashVoucher::DIRECTION_RECEIPT,
                CashVoucher::DIRECTION_PAYMENT,
            ])],
            'operation' => ['required', Rule::in([
                CashVoucher::OPERATION_GENERIC_RECEIPT,
                CashVoucher::OPERATION_GENERIC_PAYMENT,
            ])],
            'transaction_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'document_type' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,webp'],
            'account_id' => ['prohibited'],
            'cash_account_id' => ['prohibited'],
            'counter_account_id' => ['prohibited'],
            'owner_id' => ['prohibited'],
            'created_by' => ['prohibited'],
            'accounting_status' => ['prohibited'],
            'accounting_transaction_id' => ['prohibited'],
            'obj_type' => ['prohibited'],
            'obj_id' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $expectedOperation = match ($this->input('direction')) {
                CashVoucher::DIRECTION_RECEIPT => CashVoucher::OPERATION_GENERIC_RECEIPT,
                CashVoucher::DIRECTION_PAYMENT => CashVoucher::OPERATION_GENERIC_PAYMENT,
                default => null,
            };

            if ($expectedOperation !== null && $this->input('operation') !== $expectedOperation) {
                $validator->errors()->add(
                    'operation',
                    'Nghiệp vụ không phù hợp với loại giao dịch đã chọn.'
                );
            }
        });
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }
}
