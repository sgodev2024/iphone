<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SignUpService;
use App\Services\StoreService;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    protected $storeService;
    protected $signUpService;
    public function __construct(StoreService $storeService, SignUpService $signUpService)
    {
        $this->storeService = $storeService;
        $this->signUpService = $signUpService;
    }

    public function index()
    {
        try {
            $stores = $this->storeService->getAllStore();
            return view('superadmin.store.index', compact('stores'));
        } catch (Exception $e) {
            Log::error('Failed to find any store' . $e->getMessage());
            return ApiResponse::error('Failed to find any store', 500);
        }
    }
    public function findByPhone(Request $request)
    {
        try {
            $owner = $this->storeService->findOwnerByPhone($request->input('phone'));
            $stores = new LengthAwarePaginator(
                $owner ? [$owner] : [],
                $owner ? 1 : 0,
                10,
                1,
                ['path' => Paginator::resolveCurrentPath()]
            );
            return view('superadmin.store.index', compact('stores'));
        } catch (Exception $e) {
            Log::error('Failed to find store owner:' . $e->getMessage());
            return response()->json(['error' => 'Failed to find store owner'], 500);
        }
    }
    public function detail($id)
    {
        try {
            $stores = $this->storeService->findStoreByID($id);
            return view('superadmin.store.edit', compact('stores'));
        } catch (Exception $e) {
            Log::error('Cannot find store info: ' . $e->getMessage());
            return ApiResponse::error('Cannot find store info', 500);
        }
    }

    public function delete($id)
    {
        try{
            $this->storeService->deleteStore($id);
            session()->flash('success', 'Xóa thông tin khách hàng thànhc công');
            return redirect()->back();
        }
        catch (\Exception $e) {
            Log::error('Failed to delete store profile: ' . $e->getMessage());
            return ApiResponse::error('Failed to update store profile ', 500);
        }
    }

}
