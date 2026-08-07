<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\BrandService;
use App\Services\CompanyService;
use App\Services\SupplierService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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
        $uploadedLogo = null;

        return transaction(function () use ($request, $credentials, &$uploadedLogo) {
            if ($request->hasFile('logo')) {
                $uploadedLogo = $request->file('logo')->store('brands', 'public');
                $credentials['logo'] = $uploadedLogo;
            }

            $brand = Brand::create($credentials);

            return successResponse('Tạo mới thương hiệu thành công.', $brand, Response::HTTP_CREATED);
        }, function () use (&$uploadedLogo) {
            deleteImage($uploadedLogo);
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

            return successResponse('Cập nhật thương hiệu thành công.', $brand->fresh(), Response::HTTP_OK);
        });
    }

    public function delete($id)
    {
        try {
            $this->brandService->deleteBrand($id);
            $brands = Brand::orderByDesc('created_at')->paginate(10);
            $view = view('admin.brand.table', compact('brands'))->render();
            return response()->json(['success' => true, 'message' => 'Xoá thương hiệu thành công!', 'table' => $view]);
        } catch (Exception $e) {
            Log::error('Failed to delete brand: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể xóa thương hiệu']);
        }
    }

    private function validateRequest($request, $id = null)
    {
        return $this->validate($request, [
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($id)],
            'description' => 'nullable|string',
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
