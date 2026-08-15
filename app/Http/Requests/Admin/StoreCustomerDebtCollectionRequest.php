<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerDebtCollectionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => strtolower(trim((string) $this->input('payment_method'))),
            'idempotency_key' => strtolower(trim((string) $this->input('idempotency_key'))),
            'note' => ($note = trim((string) $this->input('note'))) !== '' ? $note : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('receipt.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'collection_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['required', 'in:cash,bank_transfer'],
            'money_account_id' => [
                'nullable',
                'integer',
                'required_if:payment_method,bank_transfer',
                'prohibited_unless:payment_method,bank_transfer',
                'exists:accounts,id',
            ],
            'note' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,webp'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'collection_date.before_or_equal' => 'Ngày thu không được lớn hơn ngày hiện tại.',
        ];
    }
}
