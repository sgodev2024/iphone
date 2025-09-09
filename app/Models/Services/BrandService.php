<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class BrandService
{
    protected $brand;
    public function __construct(Brand $brand)
    {
        $this->brand = $brand;
    }

    public function getAllBrand(): LengthAwarePaginator
    {
        try {
            Log::info('Fetching all categories');
            $categories = $this->brand->orderByDesc('created_at')->paginate(10);
            return $categories;
        } catch (Exception $e) {
            Log::error('Failed to fetch brand: ' . $e->getMessage());
            throw new Exception('Failed to fetch brand');
        }
    }

    public function getBrandAll()
    {
        try {
            Log::info('Fetching all categories');
            $categories = $this->brand->get();
            return $categories;
        } catch (Exception $e) {
            Log::error('Failed to fetch brand: ' . $e->getMessage());
            throw new Exception('Failed to fetch brand');
        }
    }

    /**
     * Summary of createBrand
     * @param array $data
     * @throws Exception
     * @return Brand
     */
    public function createBrand($data): Brand
    {
        $image = saveImages($data, 'images', 'brand', 300, 300);
        DB::beginTransaction();
        try {

            Log::info("Creating a new Brand with name: {$data->name}");

            $brand = $this->brand->create([
                'name' => $data->name,
                'logo' => $image,
            ]);
            DB::commit();
            return $brand;
        } catch (Exception $e) {
            deleteImage($image);
            DB::rollBack();
            Log::error("Failed to create Brand: {$e->getMessage()}");
            throw new Exception('Failed to create Brand');
        }
    }

    public function getBrandById(int $id): Brand
    {
        Log::info("Fetching product with ID: $id");
        $brand = $this->brand->find($id);

        if (!$brand) {
            Log::warning("Product with ID: $id not found");
            throw new ModelNotFoundException("Product not found");
        }
        return $brand;
    }

    public function updateBrand(int $id, $data): Brand
    {
        DB::beginTransaction();
        try {
            $brand = $this->getBrandById($id);
            Log::info("Updating product with ID: $id");

            $criteria = [
                'name' => $data->name
            ];

            if($data->hasFile('images')){
                $criteria['logo'] = saveImages($data, 'images', 'brand', 300, 300);
            }

            $update = $brand->update($criteria);

            DB::commit();
            return $brand;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to update brand: {$e->getMessage()}");
            throw new Exception('Failed to update brand');
        }
    }

    public function brandByName($name)
    {
        try {
            $brand = $this->brand->where('name', 'LIKE', '%' . $name . '%')->first();
            return $brand;
        } catch (Exception $e) {
            Log::error("Failed to search products: {$e->getMessage()}");
            throw new Exception('Failed to search products');
        }
    }

    public function deleteBrand($id)
    {
        try{
            $brand = $this->getBrandById($id);
            $brand->delete();
            DB::commit();
        }
        catch(Exception $e)
        {
            DB::rollBack();
            Log::error("Failed to delete brand:" .$e->getMessage());
            throw new Exception("Failed to delete brand");
        }
    }

    public function findBrandBySupplier($supplier_id): LengthAwarePaginator
    {
        try{
            $brand = $this->brand->where('supplier_id', $supplier_id)->paginate(10);
            return $brand;
        }
        catch(Exception $e)
        {
            Log::error('Failed to find brand: ' .$e->getMessage());
            throw new Exception('Failed to find brand');
        }
    }
}
