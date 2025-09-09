<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // cho phép người dùng sử dụng true false là không
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // khai báo 1 mảng
        $rules = [];
        // lấy phương thức đang hoạt động trên route
        $method = $this->route()->getActionMethod();
        switch ($this->method()) { // lấy phương thức mà route đó đang dùng
            case 'POST':
                switch ($method) {
                    case 'postAdd':
                        $rules = [
                            'name' => 'required|string|min:6',
                            'mota' => 'required|string',
                            'gia' => 'required|integer|min:0',
                            'anh' => 'required|image|max:2048',
                        ];
                        break;
                    case 'updateProduct':
                        $rules = [
                            'name' => 'required|string|min:6',
                            'mota' => 'required|string',
                            'gia' => 'required|integer|min:0',
                            'anh' => 'nullable|image|max:2048',
                        ];
                        break;
                    default:
                        break;
                }
                break;
            default:
                break;
        }
        return $rules;
    }

    // thay đổi thông báo mặc định
    public function messages()
    {
       return [
           'name.required' => ':attribute không để trống ',
           'name.min' => 'Vui lòng nhập trên :min ký tự',
           'mota.required' => ':attribute không để trống ',
           'gia.required'=>':attribute không để trống',
           'gia.integer'=>':attribute phải là sô',
           'gia.min'=>':attribute phải lớn hơn 0',
           'anh.required'=>'Vui lòng nhập file',
           'anh.image'=>'Vui Lòng chọn :attribute',
           'anh.max'=>':attribute không được lớn hơn 2048',
       ];
    }

    // thay dổi tên trường
    public function attributes()
    {
        return [
            'name' => 'Tên Sản Phẩm',
            'mota' => 'Mô tả Sản Phẩm',
            'gia' => ' Giá Sản Phẩm',
            'anh' => 'Ảnh',
        ];
    }
}
