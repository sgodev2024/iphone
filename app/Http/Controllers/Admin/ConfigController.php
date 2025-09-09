<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfigController extends Controller
{


    public function index()
    {
        $title = 'Thông tin cửa hàng';
        $config = Config::query()->where('user_id', Auth::id())->first();
        $banks = Bank::query()->orderBy('name')->pluck('name', 'id')->toArray();
        return view('admin.configuration.config', compact('config', 'banks', 'title'));
    }

    public function save(Request $request)
    {
        $credentials = $this->validateRequest($request);

        return transaction(function () use ($credentials, $request) {
            $userId = Auth::id();

            $config = Config::query()->where('user_id', $userId)->first();

            $oldLogo = $config->logo ?? null;

            if ($request->hasFile('logo')) {
                $credentials['logo'] = uploadImages('logo', 'logo');
            }

            $credentials['user_id'] = $userId;

            $config = Config::updateOrCreate(
                ['user_id' => $userId], // điều kiện tìm
                $credentials            // dữ liệu cập nhật / tạo mới
            );

            if ($config && $request->hasFile('logo')) {
                deleteImage($oldLogo);
            }

            return successResponse('Lưu thay đổi thành công.');
        });
    }

    private function validateRequest($request)
    {
        $userId = Auth::id(); // lấy user id hiện tại

        $rules = [
            'company_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('config', 'company_name')->ignore($userId, 'user_id'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('config', 'email')->ignore($userId, 'user_id'),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('config', 'phone')->ignore($userId, 'user_id'),
            ],
            'address' => 'required|string|max:255',
            'tax_number' => 'required|string|max:20',
            'receiver' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:20',
            'bank_id' => 'required|exists:banks,id',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        $attributes = [
            'company_name' => 'Tên cửa hàng',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'tax_number' => 'Mã số thuế',
            'receiver' => 'Chủ tài khoản',
            'bank_account_number' => 'Số tài khoản',
            'bank_id' => 'Ngân hàng',
            'logo' => 'Logo'
        ];

        return $this->validate($request, $rules, __('request.messages'), $attributes);
    }
}
