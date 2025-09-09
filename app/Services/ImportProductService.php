<?php

namespace App\Services;

use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportProductService
{

    protected $importCoupon;
    protected $importDetail;
    public function __construct(ImportCoupon $importCoupon, ImportDetail $importDetail)
    {
        $this->importCoupon = $importCoupon;
        $this->importDetail = $importDetail;
    }
    public function getImportCoupon($perPage = 10, $search = null)
    {
        $query = ImportCoupon::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('coupon_code', 'like', '%' . $search . '%')
                    ->orWhere('user_id', 'like', '%' . $search . '%')
                    ->orWhere('supplier_id', 'like', '%' . $search . '%');
            });
        }
        return $query->orderByDesc('created_at')->paginate($perPage);
    }


    public function getImportCouponByid($id)
    {
        try {
            Log::info('Fetching all ImportCoupon');
            return $this->importCoupon->find($id);
        } catch (Exception $e) {
            Log::error('Failed to fetch ImportCoupon: ' . $e->getMessage());
            throw new Exception('Failed to fetch ImportCoupon');
        }
    }

    public function addImportCoupon($data)
    {
        try {
            Log::info('Fetching add ImportCoupon');
            $importCoupon  = $this->importCoupon->create($data);
            return $importCoupon;
        } catch (Exception $e) {
            Log::error('Failed to fetch ImportCoupon: ' . $e->getMessage());
            throw new Exception('Failed to fetch add ImportCoupon');
        }
    }

    public function addImportDetail($data)
    {
        try {
            Log::info('Fetching add importDetail');
            // dd($data);
            $importDetail  = $this->importDetail->create($data);
            return $importDetail;
        } catch (Exception $e) {
            Log::error('Failed to fetch importDetail: ' . $e->getMessage());
            throw new Exception('Failed to fetch add importDetail');
        }
    }
}
