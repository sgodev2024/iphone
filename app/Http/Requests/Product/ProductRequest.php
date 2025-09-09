<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

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
            'quantity'     => 'required|integer|min:0',
            'category_id'  => 'required|exists:categories,id',
            'brand_id'     => 'nullable|exists:brands,id',
            'description'  => 'nullable|string',
            'is_featured'  => 'nullable|in:1',
            'status'       => 'required|boolean',
            'thumbnail'    =>  $id ? 'nullable' : 'required' . '|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
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
            'quantity'     => 'số lượng',
            'category_id'  => 'danh mục',
            'brand_id'     => 'thương hiệu',
            'description'  => 'mô tả',
            'is_featured'  => 'sản phẩm nổi bật',
            'status'       => 'trạng thái',
            'thumbnail'    => 'hình ảnh',
        ];
    }
}
