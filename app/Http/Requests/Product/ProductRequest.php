<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id') ?? null;
        return [
            'name'         => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'price_buy'    => 'required|numeric|min:0',
            'product_unit' => 'required|string|max:50',
            'category_id'  => 'required|exists:categories,id',
            'brands_id'    => 'nullable|exists:brands,id',
            'inventory_tracking' => ['required', Rule::in(Product::INVENTORY_TRACKING_OPTIONS)],
            'description'  => ['nullable', 'string'],
            'is_featured'  => 'nullable|in:1',
            'status'       => ['required', Rule::in(['published', 'inactive', 'scheduled'])],
            'thumbnail'    => ($id ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $productId = $this->route('id');

            if (! $productId) {
                return;
            }

            $product = Product::query()
                ->where('user_id', $this->user()?->id)
                ->find($productId);

            if (! $product) {
                return;
            }

            $newTracking = (string) $this->input('inventory_tracking');

            if (
                in_array($newTracking, Product::INVENTORY_TRACKING_OPTIONS, true)
                && $newTracking !== $product->inventory_tracking
                && ! $product->canChangeInventoryTracking()
            ) {
                $validator->errors()->add(
                    'inventory_tracking',
                    'Không thể thay đổi phương thức quản lý tồn kho vì sản phẩm đã phát sinh dữ liệu kho hoặc giao dịch.'
                );
            }
        });
    }

    public function messages(): array
    {
        return __('request.messages');
    }

    public function attributes(): array
    {
        return [
            'name'         => 'tên sản phẩm',
            'price'        => 'giá bán',
            'price_buy'    => 'giá nhập',
            'product_unit' => 'đơn vị',
            'category_id'  => 'danh mục',
            'brands_id'    => 'thương hiệu',
            'inventory_tracking' => 'phương thức quản lý tồn kho',
            'description'  => 'mô tả',
            'is_featured'  => 'sản phẩm nổi bật',
            'status'       => 'trạng thái',
            'thumbnail'    => 'hình ảnh',
        ];
    }
}
