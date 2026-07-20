<?php

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StoreOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }

                $item['product_id'] = $item['product_id'] ?? $item['id'] ?? null;
                $item['quantity'] = $item['quantity'] ?? $item['qty'] ?? null;

                return $item;
            })
            ->all();

        $this->merge(['items' => $items]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'subtotal' => ['required', 'numeric', 'min:0'],
            'discountType' => ['nullable', 'in:percent,amount'],
            'discountInput' => ['nullable', 'numeric', 'min:0'],
            'grand' => ['required', 'numeric', 'min:0'],

            'customer' => ['required', 'array'],
            'customer.id' => ['nullable', 'integer', 'exists:clients,id'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:20'],
            'customer.address' => ['nullable', 'string', 'max:500'],
            'customer.payment' => ['required', 'in:cash,bank_transfer,debt'],
            'customer.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
