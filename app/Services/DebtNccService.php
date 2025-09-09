<?php

namespace App\Services;

use App\Models\SupplierDebt;
use Exception;
use Illuminate\Support\Facades\Log;

class DebtNccService
{

    protected $supplierDebt;
    public function __construct(SupplierDebt $supplierDebt){
        $this->supplierDebt = $supplierDebt;
    }

    public function getAllSupplierDebt(){
        try {
            Log::info('Fetching all supplierDebt');
            return $this->supplierDebt->orderByDesc('created_at')->get();
        } catch (Exception $e) {
            Log::error('Failed to fetch supplierDebt: ' . $e->getMessage());
            throw new Exception('Failed to fetch supplierDebt');
        }
    }

    public function addSupplierDebt($data){
        try {
            Log::info('Fetching add supplierDebt');
            $receipts = $this->supplierDebt->create($data);
            return $receipts;
        } catch (Exception $e) {
            Log::error('Failed to  add supplierDebt: ' . $e->getMessage());
            throw new Exception('Failed to add supplierDebt');

        }
    }

    public function updateSupplierDebt($data, $supplier_id ){
        try {
            Log::info('Fetching update supplierDebt');
            $receipt = $this->supplierDebt->where('companies_id', $supplier_id )->first();
            $update = $receipt->update($data);
            return $update;
        } catch (Exception $e) {
            Log::error('Failed to  update supplierDebt: ' . $e->getMessage());
            throw new Exception('Failed to update supplierDebt');
        }
    }

    public function findSupplierDebtBySupplier($supplier_id ){
        try {
            Log::info('Fetching find supplierDebt');
            $receipt = $this->supplierDebt->where('supplier_id', $supplier_id )->first();
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find supplierDebt: ' . $e->getMessage());
            throw new Exception('Failed to find supplierDebt');
        }
    }

    public function findCompanyDebtBySupplier($supplier_id ){
        try {
            Log::info('Fetching find companies');
            $receipt = $this->supplierDebt->where('companies_id', $supplier_id )->first();
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find companies: ' . $e->getMessage());
            throw new Exception('Failed to find companies');
        }
    }

    public function delete($supplier){
        try {
            Log::info('Fetching delete supplierDebt');
            $receipt = $this->supplierDebt->where('supplier_id', $supplier)->first();


            return  $receipt ? $receipt->delete() : throw new Exception('Failed to delete supplierDebt');
        } catch (Exception $e) {
            Log::error('Failed to  delete supplierDebt: ' . $e->getMessage());
            throw new Exception('Failed to delete supplierDebt');
        }
    }

    public function findSupplierDebtById($id){
        try {
            Log::info('Fetching find supplierDebt');
            $receipt = $this->supplierDebt->find($id);
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find supplierDebt: ' . $e->getMessage());
            throw new Exception('Failed to find supplierDebt');
        }
    }
}
