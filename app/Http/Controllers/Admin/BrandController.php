<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Brand;
use App\Services\BrandService;
use App\Services\CompanyService;
use App\Services\SupplierService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    //
    protected $brandService;
    protected $supplierService;
    protected $companyService;
    public function __construct(BrandService $brandService, SupplierService $supplierService, CompanyService $companyService)
    {
        $this->brandService = $brandService;
        $this->supplierService = $supplierService;
        $this->companyService = $companyService;
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $searchText = $request->query('s');

            $brands = Brand::query()
                ->where('user_id', Auth::id())
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%{$searchText}%");
                })
                ->latest()
                ->paginate(10);

            $html = view('admin.brand.table', compact('brands'))->render();

            return response()->json(['html' => $html]);
        }

        return view('admin.brand.index');
    }

    public function create()
    {
        $title = 'Tạo mới thương hiệu';
        $brand = null;
        return view('admin.brand.form', compact('title', 'brand'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateRequest($request);

        return transaction(function () use ($request, $credentials) {

            $credentials['user_id'] = Auth::id();

            if ($request->hasFile('logo')) {
                $credentials['logo'] = uploadImages('logo', 'brands');
            }

            Brand::create($credentials);

            return successResponse(message: 'Tạo mới thương hiệu thành công.', code: Response::HTTP_CREATED);
        });
    }

    public function edit(string $id)
    {

        $brand = Brand::findOrFail($id);
        $title = "Cập nhật thương hiệu - {$brand->name}";

        return view('admin.brand.form', compact('title', 'brand'));
    }

    public function update(Request $request, string $id)
    {
        if (!$brand = Brand::query()->find($id)) return errorResponse("Không tìm thấy dữ liệu trên hệ thống!", 404);

        $credentials = $this->validateRequest($request, $id);

        return transaction(function () use ($brand, $credentials, $request) {

            $oldLogo = $brand->logo;

            if ($request->hasFile('logo')) {
                $credentials['logo'] = $request->file('logo')->store('brands', 'public');
            }

            $updated = $brand->update($credentials);

            if ($updated && $request->hasFile('logo')) {
                deleteImage($oldLogo);
            }

            return successResponse(message: 'Cập nhật thương hiệu thành công.', code: Response::HTTP_OK);
        });
    }

    public function delete($id)
    {
        try {
            $this->brandService->deleteBrand($id);
            $brand = Brand::orderByDesc('created_at')->paginate(10);
            $view = view('admin.brand.table', compact('brand'))->render();
            return response()->json(['success' => true, 'message' => 'Xoá thương hiệu thành công!', 'table' => $view]);
        } catch (Exception $e) {
            Log::error('Failed to delete brand: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể xóa thương hiệu']);
        }
    }

    private function validateRequest($request, $id = null)
    {
        return $this->validate($request, [
            'name' => 'required|max:255|unique:brands,name,' . $id,
            'description' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:0,1'
        ], __('request.messages'), [
            'name' => 'Tên thương hiệu',
            'description' => 'Mô tả',
            'logo' => 'Logo',
            'status' => 'Trạng thái'
        ]);
    }
}
