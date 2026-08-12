<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerDebtPaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => strtolower(trim((string) $this->input('payment_method'))),
            'idempotency_key' => strtolower(trim((string) $this->input('idempotency_key'))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('receipt.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,bank_transfer'],
            'bank_account_id' => [
                'nullable',
                'integer',
                'required_if:payment_method,bank_transfer',
                'prohibited_unless:payment_method,bank_transfer',
                'exists:accounts,id',
            ],
            'transaction_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
