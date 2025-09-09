<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Supplier;
use App\Services\SupplierService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index($id)
    {
        try {
            $title = "Người đại diện";
            $suppliers = $this->supplierService->getSuppliersByCompanyId($id);

            // Pass the company ID to the view
            return view('admin.supplier.index', [
                'suppliers' => $suppliers,
                'title' => $title,
                'company_id' => $id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch suppliers: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch suppliers'], 500);
        }
    }


    public function findByPhone(Request $request)
    {
        try {
            // Tìm nhà cung cấp theo số điện thoại
            $supplier = $this->supplierService->findSupplierByPhone($request->input('phone'));

            // Kiểm tra nếu nhà cung cấp không tồn tại
            if (!$supplier) {
                return redirect()->route('admin.supplier.index')->withErrors('Nhà cung cấp không tồn tại');
            }

            // Lấy danh sách người đại diện của công ty
            $companyId = $supplier->company_id;
            $suppliers = Supplier::where('company_id', $companyId)
                ->orderByDesc('created_at')
                ->paginate(10);

            // Render lại danh sách và phân trang
            $table = view('admin.supplier.table', compact('suppliers'))->render();
            $pagination = $suppliers->links('vendor.pagination.custom')->render();

            return response()->json([
                'success' => true,
                'message' => 'Tìm kiếm thành công',
                'table' => $table,
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            Log::error('Failed to find supplier: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to find supplier'], 500);
        }
    }


    public function add($company_id)
    {
        return view('admin.supplier.add', compact('company_id'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        try {
            $supplier = $this->supplierService->addSupplier($request->all());
            session()->flash('success', 'Thêm người đại diện thành công');
            return redirect()->route('admin.supplier.index', ['company_id' => $request->company_id]);
        } catch (Exception $e) {
            Log::error('Failed to create supplier: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to create supplier']);
        }
    }


    public function edit($id)
    {
        try {
            $suppliers = $this->supplierService->findSupplierById($id);
            return view('admin.supplier.edit', compact('suppliers'));
        } catch (Exception $e) {
            Log::error('Failed to find supplier information');
        }
    }

    public function update($id, Request $request)
    {
        try {
            $supplier = $this->supplierService->updateSupplier($request->all(), $id);
            session()->flash('success', 'Cập nhật thông tin người đại diện thành công');
            $companyId = $request->input('company_id');

            // Redirect to the index route with company_id
            return redirect()->route('admin.supplier.index', ['company_id' => $companyId]);
        } catch (Exception $e) {
            Log::error('Failed to update supplier information: ' . $e->getMessage());
            return ApiResponse::error('Failed to update supplier information', 500);
        }
    }

    public function delete($id)
    {
        try {
            // Xóa người đại diện và lấy ID công ty
            $companyId = $this->supplierService->deleteSupplier($id);

            // Lấy lại danh sách người đại diện của công ty
            $suppliers = Supplier::where('company_id', $companyId)
                ->orderByDesc('created_at')
                ->paginate(10);

            // Render lại phần danh sách và phân trang
            $table = view('admin.supplier.table', compact('suppliers'))->render();
            $pagination = $suppliers->links('vendor.pagination.custom')->render();

            return response()->json([
                'success' => true,
                'message' => 'Xóa người đại diện thành công!',
                'table' => $table,
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete supplier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa người đại diện'
            ]);
        }
    }
}
