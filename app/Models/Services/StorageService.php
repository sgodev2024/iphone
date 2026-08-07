<?php

namespace App\Services;

use App\Models\ProductStorage;
use App\Models\Storage;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorageService
{
    protected $storage;
    protected $productStorage;
    public function __construct(Storage $storage, ProductStorage $productStorage)
    {
        $this->storage = $storage;
        $this->productStorage = $productStorage;
    }
    public function getStorageById($id)
    {
        try{
            return $this->storage->find($id);
        }
        catch(Exception $e)
        {
            Log::error('Failed to find storage: ' .$e->getMessage());
            throw new Exception('Failed to find storage');
        }
    }
    public function getPaginatedStorage(): LengthAwarePaginator
    {
        try {
            return $this->storage->orderByDesc('created_at')->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to fetch storage: ' . $e->getMessage());
            throw new Exception('Failed to fetch storage');
        }
    }

    public function getAllStorage()
    {
        try {
            Log::info('Fetching all Storages');
            return $this->storage->get();
        } catch (Exception $e) {
            Log::error("Failed to show all storages: " . $e->getMessage());
            throw new Exception('Failed to show all storages');
        }
    }

    public function addStorage(array $data)
    {
        DB::beginTransaction();
        try {
            Log::info('Creating new Storage');
            $storage = $this->storage->create([
                'name' => $data['name'],
                'location' => $data['location'],
            ]);
            DB::commit();
            return $storage;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create new storage: ' . $e->getMessage());
            throw new Exception('Failed to create new storage');
        }
    }

    public function updateStorage($id, array $data)
    {
        DB::beginTransaction();
        try{
            $storage = $this->storage->find($id);
            $storage->update([
                'name' => $data['name'],
                'location' => $data['location'],
            ]);
            DB::commit();
            return $storage;
        }
        catch(Exception $e)
        {
            DB::rollBack();
            Log::error('Failed to update storage: ' .$e->getMessage());
            throw new Exception('Failed to update storage');
        }
    }



    public function findStorageByName($name)
    {
        try {
            $storage = $this->storage->where('name', 'LIKE', '%' . $name . '%')->get();
            return $storage;
        } catch (Exception $e) {
            Log::error('Failed to find storage: ' . $e->getMessage());
            throw new Exception('Failed to find storage');
        }
    }


    public function deleteStorage($id)
    {
        try {
            $storage = $this->storage->find($id);
            $storage->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove storage from system: ' . $e->getMessage());
            throw new Exception('Failed to remove storage');
        }
    }

    public function getProductInStorage($id)
    {
        try{
            return $this->productStorage->where('storage_id', $id)->orderByDesc('created_at')->paginate(10);
        }
        catch(Exception $e)
        {
            Log::error("Failed to find storage's detail: " .$e->getMessage());
            throw new Exception("Failed to find storage's detail");
        }
    }
}
