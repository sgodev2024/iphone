<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Supplier;
use App\Support\BranchContext;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SupplierController extends Controller
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function index($id)
    {
        $company = $this->companyQuery()->findOrFail($id);

        try {
            $title = 'Nguoi dai dien';
            $suppliers = Supplier::query()
                ->where('company_id', $company->id)
                ->latest()
                ->paginate(10);

            return view('admin.supplier.index', [
                'suppliers' => $suppliers,
                'title' => $title,
                'company_id' => $company->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch suppliers: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch suppliers'], 500);
        }
    }

    public function findByPhone(Request $request)
    {
        try {
            $supplier = $this->supplierQuery()
                ->where('phone', $request->input('phone'))
                ->first();

            if (! $supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nha cung cap khong ton tai',
                ], 404);
            }

            $companyId = $supplier->company_id;
            $suppliers = $this->supplierQuery()
                ->where('company_id', $companyId)
                ->orderByDesc('created_at')
                ->paginate(10);

            $table = view('admin.supplier.table', compact('suppliers'))->render();
            $pagination = $suppliers->links('vendor.pagination.custom')->render();

            return response()->json([
                'success' => true,
                'message' => 'Tim kiem thanh cong',
                'table' => $table,
                'pagination' => $pagination,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to find supplier: ' . $e->getMessage());

            return response()->json(['error' => 'Failed to find supplier'], 500);
        }
    }

    public function add($company_id)
    {
        $this->companyQuery()->findOrFail($company_id);

        return view('admin.supplier.add', compact('company_id'));
    }

    public function store(Request $request)
    {
        $credentials = $this->validateSupplier($request);
        $this->companyQuery()->findOrFail($credentials['company_id']);

        try {
            $supplier = Supplier::create($credentials);
        } catch (Throwable $e) {
            Log::error('Failed to create supplier: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Khong the tao nguoi dai dien. Chi tiet loi da duoc ghi vao log.']);
        }

        session()->flash('success', 'Them nguoi dai dien thanh cong');

        return redirect()->route('admin.supplier.index', ['company_id' => $supplier->company_id]);
    }

    public function edit($id)
    {
        try {
            $suppliers = $this->supplierQuery()->findOrFail($id);

            return view('admin.supplier.edit', compact('suppliers'));
        } catch (Exception $e) {
            Log::error('Failed to find supplier information: ' . $e->getMessage());

            abort(404);
        }
    }

    public function update($id, Request $request)
    {
        $supplier = $this->supplierQuery()->findOrFail($id);
        $credentials = $this->validateSupplier($request, $id);
        $this->companyQuery()->findOrFail($credentials['company_id']);

        try {
            $supplier->update($credentials);
        } catch (Throwable $e) {
            Log::error('Failed to update supplier information: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Khong the cap nhat nguoi dai dien. Chi tiet loi da duoc ghi vao log.']);
        }

        session()->flash('success', 'Cap nhat thong tin nguoi dai dien thanh cong');

        return redirect()->route('admin.supplier.index', ['company_id' => $supplier->company_id]);
    }

    public function delete($id)
    {
        $supplier = $this->supplierQuery()->findOrFail($id);

        try {
            $companyId = $supplier->company_id;
            $supplier->delete();

            $suppliers = $this->supplierQuery()
                ->where('company_id', $companyId)
                ->orderByDesc('created_at')
                ->paginate(10);

            $table = view('admin.supplier.table', compact('suppliers'))->render();
            $pagination = $suppliers->links('vendor.pagination.custom')->render();

            return response()->json([
                'success' => true,
                'message' => 'Xoa nguoi dai dien thanh cong!',
                'table' => $table,
                'pagination' => $pagination,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete supplier: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Khong the xoa nguoi dai dien',
            ]);
        }
    }

    private function validateSupplier(Request $request, $id = null): array
    {
        return $request->validate(
            [
                'company_id' => ['required', 'integer', 'exists:companies,id'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($id)],
                'phone' => ['nullable', 'string', 'max:20'],
            ],
            __('request.messages'),
            [
                'company_id' => 'Nha cung cap',
                'name' => 'Ten nguoi dai dien',
                'email' => 'Email',
                'phone' => 'So dien thoai',
            ]
        );
    }

    private function companyQuery(): Builder
    {
        return $this->branchContext->scope(Company::query(), Auth::user());
    }

    private function supplierQuery(): Builder
    {
        $companies = $this->companyQuery()->select('id');

        return Supplier::query()->whereIn('company_id', $companies);
    }
}
