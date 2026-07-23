<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Config;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ConfigSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'config@example.test'],
            [
                'name' => 'Tài khoản cấu hình mẫu',
                'phone' => '0900000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role_id' => 1,
                'address' => 'Chưa cấu hình địa chỉ',
                'company_name' => 'Cửa hàng cấu hình mẫu',
                'store_name' => 'Cửa hàng cấu hình mẫu',
                'tax_code' => 'CHUA-CAU-HINH',
            ]
        );

        $bank = Bank::query()
            ->where('code', 'MB')
            ->orWhere('shortName', 'MBBank')
            ->first();

        if (!$bank) {
            $bank = Bank::updateOrCreate(
                ['code' => 'DEFAULT'],
                [
                    'name' => 'Ngân hàng cấu hình mẫu',
                    'bin' => '000000',
                    'shortName' => 'DEFAULT',
                ]
            );
        }

        $bankAccount = '0000000000';

        Config::updateOrCreate(
            ['user_id' => $owner->id],
            [
                'bank_id' => $bank->id,
                'bank_account' => $bankAccount,
                'receiver' => 'Chưa cấu hình người nhận',
                'logo' => 'assets/img/default-image.jpg',
                'qr' => "https://img.vietqr.io/image/{$bank->code}-{$bankAccount}-compact.jpg",
            ]
        );
    }
}
