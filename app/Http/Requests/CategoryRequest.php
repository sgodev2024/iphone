<?php

namespace App\Http\Requests;

use App\Models\Categories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $category = $id ? Categories::query()->find($id) : null;

        if ($category
            && Schema::hasColumn('categories', 'branch_id')
            && ! $this->user()?->isAdministrator()
            && (int) $category->branch_id !== (int) $this->user()?->branch_id
        ) {
            abort(404);
        }
        $branchId = $category?->branch_id;

        if ($branchId === null) {
            $branchId = $this->user()?->isAdministrator()
                ? $this->input('branch_id')
                : $this->user()?->branch_id;
        }

        $uniqueName = Rule::unique('categories', 'name')->ignore($id);
        if (Schema::hasColumn('categories', 'branch_id')) {
            $uniqueName->where(fn ($query) => $branchId
                ? $query->where('branch_id', (int) $branchId)
                : $query->whereRaw('1 = 0'));
        }

        $rules = [
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([1, 0, '1', '0'])],
        ];

        if (Schema::hasColumn('categories', 'branch_id')) {
            $rules['branch_id'] = $this->user()?->isAdministrator() && ! $category
                ? ['required', 'integer', 'exists:branches,id']
                : ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return __('request.messages');
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên danh mục',
            'description' => 'mô tả',
            'status' => 'trạng thái',
            'branch_id' => 'cửa hàng',
        ];
    }
}
