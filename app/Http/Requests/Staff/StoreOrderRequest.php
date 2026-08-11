<?php

namespace App\Http\Requests\Staff;

use App\Models\ProductImei;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
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
                $item['unit_price'] = $item['unit_price'] ?? $item['price'] ?? null;
                $item['tracking_type'] = $item['tracking_type'] ?? null;
                $item['product_imei_id'] = $item['product_imei_id'] ?? null;

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
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.tracking_type' => ['nullable', 'in:imei,quantity'],
            'items.*.product_imei_id' => ['nullable', 'integer', 'exists:product_imeis,id'],
            'items.*.imei' => ['nullable', 'string', 'max:' . ProductImei::IMEI_MAX_LENGTH],
            'items.*.barcode' => ['nullable', 'string', 'max:50'],

            'subtotal' => ['required', 'numeric', 'min:0'],
            'discountType' => ['nullable', 'in:percent,amount'],
            'discountInput' => ['nullable', 'numeric', 'min:0'],
            'grand' => ['required', 'numeric', 'min:0'],

            'customer' => ['required', 'array'],
            'customer.id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->whereNull('deleted_at'),
            ],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:20'],
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
