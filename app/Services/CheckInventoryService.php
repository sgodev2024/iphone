<?php

namespace App\Services;

use App\Models\CheckInventory;
use App\Models\warehome;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;



/**
 * Summary of CheckInventory
 */
class CheckInventoryService
{

    protected $checkInventory;

    public function __construct(CheckInventory $checkInventory)
    {
        $this->checkInventory = $checkInventory;
    }

    public function getAllCheckInventory(): LengthAwarePaginator
    {

        try {
            $checkInventory = $this->checkInventory->orderByDesc('created_at')->paginate(10);
            return $checkInventory;
        } catch (Exception $e) {
            Log::error('Failed to get all checkInventory: ' . $e->getMessage());
            throw new Exception('Failed to get all checkInventory');
        }
    }

    /**
     * Summary of getCheckInventoryById
     * @param mixed $id
     * @throws Exception
     * @return CheckInventory
     */
    public function filterCheck($startDate, $endDate, $phone)
    {
        try {
            $query = $this->checkInventory->query();

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            if ($phone) {
                $query->whereHas('user', function ($query) use ($phone) {
                    $query->where('phone', $phone);
                });
            }

            $check = $query->paginate(10);
            return $check;
        } catch (Exception $e) {
            Log::error('Failed to find check tickets: ' . $e->getMessage());
            throw new Exception('Failed to find check tickets');
        }
    }
    public function getCheckInventoryById($id)
    {

        try {
            $checkInventory = $this->checkInventory->find($id);
            return $checkInventory;
        } catch (Exception $e) {
            Log::error('Failed to get  checkInventory by ' . $id . '-' . $e->getMessage());
            throw new Exception('Failed to get  checkInventory by ' . $id);
        }
    }

    public function addCheckInventory(array $data)
    {
        try {
            $slTang = 0;
            $slGiam = 0;
            $warehomes = warehome::whereNotNull('reality')->get();
            foreach ($warehomes as $key => $value) {
                    if($value->difference >= 0){
                        $slTang += $value->difference;
                    }else{
                        $slGiam += $value->difference;
                    }
            }
            $checkInventory = $this->checkInventory->create([
                'user_id' => $data['user_id'],
                'note' => $data['note'],
                'tong_chenh_lech' => $slTang + $slGiam,
                'sl_tang' => $slTang,
                'sl_giam' => $slGiam
            ]);
            return $checkInventory;
        } catch (Exception $e) {
            Log::error('Failed to  add checkInventory' . $e->getMessage());
            throw new Exception('Failed to add checkInventory by ');
        }
    }
}
