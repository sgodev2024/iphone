<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\ImportCoupon;
use App\Models\ImportDetail;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyProductService{
    protected $companyProduct, $product, $company, $importCoupon, $importDetail;

    public function __construct(CompanyProduct $companyProduct, Company $company, Product $product, ImportCoupon $importCoupon, ImportDetail $importDetail)
    {
        $this->companyProduct = $companyProduct;
        $this->company = $company;
        $this->product = $product;
        $this->importCoupon = $importCoupon;
        $this->importDetail = $importDetail;
    }

    public function updateCompanyProduct($productId, $companyId)
    {
        DB::beginTransaction();
        try{
            $companyProduct = $this->companyProduct->firstOrNew([
                'product_id' => $productId,
                'company_id' => $companyId,
            ]);

            $companyProduct->save();
            DB::commit();
            return $companyProduct;
        }
        catch(Exception $e)
        {
            DB::rollBack();
            Log::error("Failed to update product's company: " .$e->getMessage());
            throw new Exception("Failed to update product's company");
        }
    }
}
