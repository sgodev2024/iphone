<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Bank;
use App\Services\AdminService;
use App\Services\SupperAdminService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuperAdminController extends Controller
{
    protected $adminService;
    protected $supperAdminService;

    public function __construct(AdminService $adminService, SupperAdminService $supperAdminService)
    {
        $this->adminService = $adminService;
        $this->supperAdminService = $supperAdminService;
    }

    public function getSuperAdminInfor($id)
    {
        try {
            $bank = Bank::get();
            $sa = $this->adminService->getSuperAdminById($id);
            return view('superadmin.profile.detail', compact('sa', 'bank'));
        } catch (Exception $e) {
            Log::error('Failed to fetch super admin info: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch super admin info', 500);
        }
    }

    public function updateSuperAdminInfo(Request $request, $id)
    {
        try {
            $sa = $this->adminService->updateSuperAdmin($id, $request->all());
            // dd($sa);
            $authUser = session('authSuper');
            $authUser->name = $sa->name;
            // dd($authUser->name);
            $authUser->email =  $sa->email;
            // dd($authUser->email);
            // $authUser->user_info->img_url = $sa->user_info->img_url;
            // dd($authUser);
            session(['authSuper' => $authUser]);
            Log::info('Successfully updated super admin profile');
            session()->flash('success', 'Thay đổi thông tin thành công');
            return redirect()->back();
        } catch (Exception $e) {
            Log::error('Failed to update admin info: ' . $e->getMessage());
            return ApiResponse::error('Failed to update admin info', 500);
        }
    }


    public function loginForm()
    {
        return view('superadmin.formlogin.index');
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->only(['email', 'password']);
            $result = $this->supperAdminService->authenticateSupper($credentials);
            session()->put('authSuper', $result['supper']);
            return redirect()->route('super.store.index');
        } catch (Exception $e) {
            return $this->handleLoginError($request, $e);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush();
        return redirect()->route('super.dang.nhap');
    }
    protected function handleLoginError($request, \Exception $e)
    {
        return redirect()->back()->withErrors(['error' => $e->getMessage()]);
    }
}
