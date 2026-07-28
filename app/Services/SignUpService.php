<?php

namespace App\Services;

use App\Models\City;
use App\Models\Config;
use App\Models\Field;
use App\Models\SuperAdmin;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SignUpService
{
    private const DEFAULT_LOGO = 'logo/17841017266a573b5e296c9.webp';

    protected $user;
    protected $city;
    protected $field;
    protected $superAdmin;

    public function __construct(User $user, City $city, Field $field, SuperAdmin $superAdmin)
    {
        $this->user = $user;
        $this->city = $city;
        $this->field = $field;
        $this->superAdmin = $superAdmin;
    }

    public function signup(array $data)
    {
        DB::beginTransaction();

        try {
            Log::info("Creating new account: {$data['name']}");

            $password = $this->RenderRandomPassword();
            $hashedPassword = Hash::make($password);

            $user = $this->user->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'company_name' => $data['company_name'],
                'dob' => $data['dob'],
                'password' => $hashedPassword,
                'status' => 'active',
                'role_id' => 1,
                'city_id' => $data['city'],
                'tax_code' => $data['tax_code'],
                'store_name' => $data['store_name'],
                'field_id' => $data['field'],
                'domain' => $data['store_domain'],
                'address' => $data['address'],
            ]);

            Config::create([
                'user_id' => $user->id,
                'logo' => self::DEFAULT_LOGO,
                'receiver' => 'Chưa cấu hình người nhận',
            ]);

            DB::commit();

            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create new account: ' . $e->getMessage());

            throw new Exception('Failed to create new account');
        }
    }

    public function RenderRandomPassword()
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        $allCharacters = $uppercase . $lowercase . $numbers;
        $password = '';

        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];

        for ($i = 3; $i < 8; $i++) {
            $password .= $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        return str_shuffle($password);
    }

    public function getAllCities()
    {
        try {
            return $this->city->all();
        } catch (Exception $e) {
            Log::error('Failed to fetch all cities: ' . $e->getMessage());

            throw new Exception('Failed to fetch all city');
        }
    }

    public function getAllFields()
    {
        try {
            return $this->field->all();
        } catch (Exception $e) {
            Log::error('Failed to fetch all fields: ' . $e->getMessage());

            throw new Exception('Failed to fetch all field');
        }
    }
}
