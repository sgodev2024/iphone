<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'staff@example.test'],
            [
                'name' => 'Nhân viên bán hàng mẫu',
                'phone' => '0900000003',
                'password' => Hash::make('password'),
                'status' => 'active',
                'role_id' => 3,
            ]
        );

        $missingSampleUsers = max(0, 20 - User::query()->where('role_id', 2)->count());

        if ($missingSampleUsers > 0) {
            User::factory()->count($missingSampleUsers)->create();
        }
    }
}
