<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
            'name' => 'required|max:255|unique:companies,name,' . $id,
            'phone' => 'required|regex:/^[0-9]{10,11}$/|unique:companies,phone,' . $id,
            'email' => 'required|email|unique:companies,email,' . $id,
            'address' => 'required|max:255',
            'tax_number' => 'required|max:255',
            'bank_account' => 'required|max:255',
            'bank_id' => 'required|exists:banks,id',
            'note' => 'nullable|max:255',
            'city_id' => 'required|exists:city,id',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return __('request.messages');
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên nhà cung cấp',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'address' => 'Địa chỉ',
            'tax_number' => 'Mã số thuế',
            'bank_account' => 'Số tài khoản ngân hàng',
            'bank_id' => 'Ngân hàng',
            'note' => 'Ghi chú',
            'city_id' => 'Thành phố',
            'status' => 'Trạng thái',
        ];
    }
}
