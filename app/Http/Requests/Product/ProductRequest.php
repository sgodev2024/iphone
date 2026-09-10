<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_buy' => ['required', 'numeric', 'min:0'],
            'product_unit' => ['required', 'string', 'max:50'],
            'inventory_tracking' => ['required', Rule::in(Product::INVENTORY_TRACKING_OPTIONS)],
            'description' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'in:1'],
            'status' => ['required', Rule::in(['published', 'inactive', 'scheduled'])],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if (! Schema::hasColumn('products', 'branch_id')) {
            $rules['category_id'] = ['required', 'exists:categories,id'];
            $rules['brands_id'] = ['nullable', 'exists:brands,id'];

            return $rules;
        }

        $product = $this->route('id')
            ? Product::query()->find($this->route('id'))
            : null;

        if ($product
            && ! $this->user()?->isAdministrator()
            && (int) $product->branch_id !== (int) $this->user()?->branch_id
        ) {
            abort(404);
        }
        $branchId = $product?->branch_id;

        if ($branchId === null) {
            $branchId = $this->user()?->isAdministrator()
                ? $this->input('branch_id')
                : $this->user()?->branch_id;
        }

        $rules['branch_id'] = $this->user()?->isAdministrator() && ! $product
            ? ['required', 'integer', 'exists:branches,id']
            : ['nullable'];
        $rules['category_id'] = [
            'required',
            Rule::exists('categories', 'id')->where(
                fn ($query) => $branchId
                    ? $query->where('branch_id', (int) $branchId)
                    : $query->whereRaw('1 = 0')
            ),
        ];
        $rules['brands_id'] = ['nullable'];
        $brandExists = Rule::exists('brands', 'id');
        if (Schema::hasColumn('brands', 'branch_id')) {
            $brandExists->where(
                fn ($query) => $branchId
                    ? $query->where('branch_id', (int) $branchId)
                    : $query->whereRaw('1 = 0')
            );
        }
        $rules['brands_id'][] = $brandExists;

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $productId = $this->route('id');

            if (! $productId) {
                return;
            }

            $user = $this->user();
            $productQuery = Product::query();

            if (! $user?->isAdministrator()) {
                if (Schema::hasColumn('products', 'branch_id')) {
                    $productQuery->where('branch_id', $user?->branch_id);
                } else {
                    $productQuery->where('user_id', $user?->id);
                }
            }

            $product = $productQuery->find($productId);

            if (! $product) {
                return;
            }

            $newTracking = (string) $this->input('inventory_tracking');

            if (in_array($newTracking, Product::INVENTORY_TRACKING_OPTIONS, true)
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
            'name' => 'Tên sản phẩm',
            'price' => 'Giá bán',
            'price_buy' => 'Giá nhập',
            'product_unit' => 'Đơn vị',
            'category_id' => 'Danh mục',
            'brands_id' => 'Thương hiệu',
            'inventory_tracking' => 'Phương thức quản lý tồn kho',
            'description' => 'Mô tả',
            'is_featured' => 'Sản phẩm nổi bật',
            'status' => 'Trạng thái',
            'thumbnail' => 'Hình ảnh',
            'branch_id' => 'Cửa hàng',
        ];
    }
}
