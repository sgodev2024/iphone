<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id') ?? null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($id)],
            'description' => 'nullable|string|max:255',
            'status' => 'required|in:1,0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.*
     * @return array
     */
    public function messages()
    {
        return __('request.messages');
    }

    public function attributes()
    {
        return [
            'name' => 'tên danh mục',
            'description' => 'mô tả',
            'status' => 'trạng thái',
        ];
    }
}
