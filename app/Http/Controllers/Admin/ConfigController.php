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
    private const DEFAULT_LOGO = 'logo/17841017266a573b5e296c9.webp';


    public function index()
    {
        $title = 'Thông tin cửa hàng';
        $config = Config::with(['bank', 'user'])->where('user_id', Auth::id())->first();
        $banks = Bank::query()->orderBy('name')->pluck('name', 'id')->toArray();
        return view('admin.configuration.config', compact('config', 'banks', 'title'));
    }

    public function save(Request $request)
    {
       
        $credentials = $this->validateRequest($request);
       

        return transaction(function () use ($credentials, $request) {
            $user = Auth::user();
            $userId = $user->id;

            $config = Config::query()->where('user_id', $userId)->first();

            $oldLogo = $config->logo ?? null;

            if ($request->hasFile('logo')) {
                $credentials['logo'] = uploadImages('logo', 'logo');
            }

            $bank = Bank::findOrFail($credentials['bank_id']);
            $bankAccount = $credentials['bank_account'];

            $user->store_name = $credentials['company_name'];
            $user->company_name = $credentials['company_name'];
            $user->email = $credentials['email'];
            $user->phone = $credentials['phone'];
            $user->address = $credentials['address'];
            $user->tax_code = $credentials['tax_number'];
            $user->save();

            $config = Config::updateOrCreate(
                ['user_id' => $userId],
                [
                    'bank_id' => $bank->id,
                    'bank_account' => $bankAccount,
                    'receiver' => $credentials['receiver'],
                    'logo' => $credentials['logo'] ?? $oldLogo ?? self::DEFAULT_LOGO,
                    'qr' => "https://img.vietqr.io/image/{$bank->code}-{$bankAccount}-compact.jpg",
                ]
            );

            if ($config && $request->hasFile('logo') && normalizePublicImagePath($oldLogo) !== self::DEFAULT_LOGO) {
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
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'address' => 'required|string|max:255',
            'tax_number' => 'required|string|max:20',
            'receiver' => 'required|string|max:255',
            'bank_account' => 'required|string|max:20',
            'bank_id' => 'required|exists:banks,id',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $attributes = [
            'company_name' => 'Tên cửa hàng',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'tax_number' => 'Mã số thuế',
            'receiver' => 'Chủ tài khoản',
            'bank_account' => 'Số tài khoản',
            'bank_id' => 'Ngân hàng',
            'logo' => 'Logo'
        ];

        return $this->validate($request, $rules, __('request.messages'), $attributes);
    }
}
