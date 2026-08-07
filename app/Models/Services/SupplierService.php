<?php

namespace App\Services;

use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SupplierService
{
    protected $supplier;
    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function GetAllSupplier()
    {
        try {
            return $this->supplier->orderByDesc('created_at')->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to fetch Supplier : ' . $e->getMessage());
            throw new Exception('Failed to fetch Supplier');
        }
    }
    public function getSuppliersByCompanyId($companyId)
    {
        try {
            return $this->supplier
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } catch (\Exception $e) {
            Log::error('Failed to get suppliers by company ID: ' . $e->getMessage());
            throw new \Exception('Failed to fetch suppliers');
        }
    }
    public function GetSuppliersAll()
    {
        try {
            return $this->supplier->get();
        } catch (Exception $e) {
            Log::error('Failed to fetch Supplier : ' . $e->getMessage());
            throw new Exception('Failed to fetch Supplier');
        }
    }

    public function findSupplierByPhone($phone)
    {
        try {
            $supplier = $this->supplier->where('phone', $phone)->first();
            return $supplier;
        } catch (Exception $e) {
            Log::error('Failed to find supplier information: ' . $e->getMessage());
            throw new Exception('Failed to find supplier informations');
        }
    }

    public function addSupplier(array $data)
    {
        DB::beginTransaction();
        try {
            $supplier = $this->supplier->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'company_id' => $data['company_id'] // Thêm công ty ID
            ]);
            DB::commit();
            return $supplier;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed Add Supplier : " . $e->getMessage());
            throw new Exception("Failed Add Supplier");
        }
    }


    public function findSupplierById($id)
    {
        try {
            return $this->supplier->find($id);
        } catch (Exception $e) {
            Log::error('Failed to find suppler: ' . $e->getMessage());
            throw new Exception('Failed to find supplier');
        }
    }

    public function updateSupplier(array $data, $id)
    {
        DB::beginTransaction();
        try {
            $supplier = $this->supplier->find($id);
            $supplier->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
            DB::commit();
            return $supplier;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed update Supplier : " . $e->getMessage());
            throw new Exception("Failed update Supplier");
        }
    }

    public function deleteSupplier($id)
    {
        DB::beginTransaction();
        try {
            $supplier = Supplier::findOrFail($id); // Tìm người đại diện
            $companyId = $supplier->company_id; // Lưu ID công ty trước khi xóa
            $supplier->delete(); // Xóa người đại diện
            DB::commit();
            return $companyId; // Trả về ID công ty
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete supplier: ' . $e->getMessage());
            throw new Exception('Failed to delete supplier');
        }
    }
}
