<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Roles;
use App\Models\Config;
use App\Models\User;
use App\Models\UserInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ConfigSeeder extends Seeder
{
    private const DEFAULT_LOGO = 'logo/17841017266a573b5e296c9.webp';
    private const DEFAULT_AVATAR = 'avatar/17841016176a573af1aa503.webp';

    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'config@example.test'],
            [
                'name' => 'Admin',
                'phone' => '0900000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role_id' => Roles::administratorId(),
                'address' => 'Chưa cấu hình địa chỉ',
                'company_name' => 'Cửa hàng cấu hình mẫu',
                'store_name' => 'Cửa hàng cấu hình mẫu',
                'tax_code' => 'CHUA-CAU-HINH',
            ]
        );

        UserInfo::updateOrCreate(
            ['user_id' => $owner->id],
            ['img_url' => self::DEFAULT_AVATAR]
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
                'logo' => self::DEFAULT_LOGO,
                'qr' => "https://img.vietqr.io/image/{$bank->code}-{$bankAccount}-compact.jpg",
            ]
        );
    }
}
