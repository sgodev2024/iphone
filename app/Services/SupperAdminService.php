<?php

namespace App\Services;

use App\Models\SuperAdmin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SupperAdminService
{
    protected $superAdmin;
    public function __construct(SuperAdmin $superAdmin)
    {
        $this->superAdmin = $superAdmin;
    }

    public function authenticateSupper($credentials)
    {

        $supper = SuperAdmin::where('email', $credentials['email'])->orwhere('phone', $credentials['email'])->first();
        if (!$supper) {
            throw new Exception('Not an supper');
        }
        if (!Hash::check($credentials['password'], $supper->password)) {
            Log::info('User logged in successfully', ['supper' => $supper]);
            throw new Exception('Unauthorized');
        }
        Auth::login($supper);
        return ['supper' => $supper];
    }
}
