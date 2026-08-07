<?php

namespace App\Services;

use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\UserInfo;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Summary of AdminService
 */
class AdminService
{
    protected $user;
    protected $superAdmin;
    public function __construct(User $user, SuperAdmin $superAdmin)
    {
        $this->user = $user;
        $this->superAdmin = $superAdmin;
    }
    public function getUserById(int $id): User
    {
        Log::info("Fetching user with ID: $id");
        $user = $this->user->find($id);
        if (!$user) {
            Log::warning("User with ID: $id not found");
            throw new ModelNotFoundException("User not found");
        }
        return $user;
    }
    public function getSuperAdminById(int $id): SuperAdmin
    {
        Log::info("Fetching user with ID: $id");
        $user = $this->superAdmin->find($id);
        if (!$user) {
            Log::warning("User with ID: $id not found");
            throw new ModelNotFoundException("User not found");
        }
        return $user;
    }
    public function updateUser(int $id,  $data): User
    {
        $image = saveImages($data, 'img_url', 'avatar', 300, 300);

        DB::beginTransaction();
        try {
            $criteria = $data->all();
            $admin = $this->getUserById($id);
            Log::info("Updating user with ID: $id");

            if ($image) {
                $criteria['img_url'] = $image;
            }

            $admin->update($criteria);

            auth()->setUser($admin);

            DB::commit();
            return $admin;
        } catch (Exception $e) {
            deleteImage($image);
            DB::rollBack();
            Log::error("Failed to update user: {$e->getMessage()}");
            throw $e;
        }
    }
    public function updateSuperAdmin(int $id, array $data): SuperAdmin
    {
        DB::beginTransaction();
        try {
            $admin = $this->getSuperAdminById($id);
            Log::info("Updating user with ID: $id");

            // Update the admin data
            $admin->update($data);
            // Check if there is an image to update
            // if (isset($data['img_url']) && $data['img_url']->isValid()) {
            //     $image = $data['img_url'];
            //     $imageFileName = 'image_' . $image->getClientOriginalName();
            //     $imageFilePath = 'storage/admin/' . $imageFileName;
            //     Storage::putFileAs('public/admin', $image, $imageFileName);

            //     // Ensure the user_info relationship is loaded
            //     $userInfo = $admin->user_info;
            //     if ($userInfo) {
            //         $userInfo->img_url = $imageFilePath;
            //         $userInfo->save();
            //     } else {
            //         // Handle the case where user_info is not present
            //         $userInfo = new UserInfo();
            //         $userInfo->user_id = $admin->id;
            //         $userInfo->img_url = $imageFilePath;
            //         $userInfo->save();
            //     }
            // }

            DB::commit();
            return $admin;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to update user: {$e->getMessage()}");
            throw $e;
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword)
    {
        $admin = User::findOrFail($userId);

        if (!Hash::check($currentPassword, $admin->password)) {
            return [
                'status' => 'error',
                'message' => 'Mật khẩu hiện tại không đúng !'
            ];
        }
        if ($newPassword === $currentPassword) {
            return [
                'status' => 'error',
                'message' => 'Mật khẩu mới không được trùng mật khẩu cũ!',
            ];
        }
        if ($newPassword !== $confirmPassword) {
            return [
                'status' => 'error',
                'message' => 'Xác nhận mật khẩu không đúng !'
            ];
        }

        $admin->password = Hash::make($newPassword);
        $admin->save();

        return [
            'status' => 'success',
            'message' => 'Đổi mật khẩu thành công !'
        ];
    }


    /**
     * Summary of getStaff
     * @return LengthAwarePaginator
     */
    public function getStaff(): LengthAwarePaginator
    {
        DB::beginTransaction();
        try {
            $admin = $this->user->where('role_id', 2)->orderByDesc('created_at')->paginate(10);
            DB::commit();
            return $admin;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to  staff: {$e->getMessage()}");
            throw $e;
        }
    }
    public function findStaffByPhone($phone)
    {
        try {
            $staff = $this->user
                ->where('phone', $phone)
                ->where('role_id', 2)
                ->first();
            return $staff;
        } catch (Exception $e) {
            Log::error('Failed to find client profile: ' . $e->getMessage());
            throw new Exception('Failed to find client profile');
        }
    }
    /**
     * Summary of addStaff
     */
    public function addStaff($data): User
    {
        DB::beginTransaction();
        try {
            $admin = $this->user->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'phone' => $data['phone'],
                'address' => $data['address'],
                'role_id' => $data['role_id'],
                'storage_id' => $data['storage'],
                'status' => 'active'
            ]);
            DB::commit();
            return $admin;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to add staff: {$e->getMessage()}");
            throw $e;
        }
    }

    public function deleteStaff(int $id): void
    {
        DB::beginTransaction();
        try {
            $staff = $this->getUserById($id);
            $staff->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete staff: {$e->getMessage()}");
            throw new Exception('Failed to delete staff');
        }
    }
}
