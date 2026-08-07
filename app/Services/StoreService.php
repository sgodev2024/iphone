<?php

namespace App\Services;

use App\Mail\UserRegistered;
use App\Models\Config;
use App\Models\User;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StoreService
{
    protected $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getAllStore(): LengthAwarePaginator
    {
        try {
            return $this->user->where('role_id', 1)->orderByDesc('created_at')->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to fetch stores: ' . $e->getMessage());
            throw new Exception('Failed to fetch stores');
        }
    }

    public function findStoreByID(int $id)
    {
        try {
            return $this->user->find($id);
        } catch (Exception $e) {
            Log::error('Failed to find store info: ' . $e->getMessage());
            throw new Exception('Failed to find store info');
        }
    }

    public function findOwnerByPhone($phone)
    {
        try {
            $staff = $this->user
                ->where('phone', $phone)
                ->where('role_id', 1)
                ->first();
            return $staff;
        } catch (Exception $e) {
            Log::error('Failed to find client profile: ' . $e->getMessage());
            throw new Exception('Failed to find client profile');
        }
    }

    public function deleteStore($id)
    {
        try{
            Log::info("Deleting store");
            $store = $this->user->find($id);
            $store->delete();
            DB::commit();
        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete store profile: ' . $e->getMessage());
            throw new Exception('Failed to delete store profile');
        }
    }
}
