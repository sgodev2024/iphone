<?php

namespace App\Services;

use App\Models\Receipts;
use Exception;
use Illuminate\Support\Facades\Log;

class ReceiptsService
{
    protected $receipts;
    public function __construct(Receipts $receipts){
        $this->receipts = $receipts;
    }

    public function getAllReceiptsPage(){
        try {
            Log::info('Fetching all receipts');
            return $this->receipts->orderByDesc('updated_at')->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to fetch receipts: ' . $e->getMessage());
            throw new Exception('Failed to fetch receipts');
        }
    }


    public function getAllReceipts(){
        try {
            Log::info('Fetching all receipts');
            return $this->receipts->orderByDesc('updated_at')->get();
        } catch (Exception $e) {
            Log::error('Failed to fetch receipts: ' . $e->getMessage());
            throw new Exception('Failed to fetch receipts');
        }
    }

    public function addReceipts($data){
        try {
            Log::info('Fetching add receipts');
            $receipts = $this->receipts->create($data);
            return $receipts;
        } catch (Exception $e) {
            Log::error('Failed to  add receipts: ' . $e->getMessage());
            throw new Exception('Failed to add receipts');
        }
    }

    public function findRecieptByClient($client){
        try {
            Log::info('Fetching find receipts by client');
            $receipt = $this->receipts->where('client_id', $client)->first();
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  add receipts: ' . $e->getMessage());
            throw new Exception('Failed to find receipts by client');
        }
    }

    public function findReceiptById($id){
        try {
            Log::info('Fetching find receipts ');
            $receipt = $this->receipts->find($id);
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  add receipts: ' . $e->getMessage());
            throw new Exception('Failed to find receipts');
        }
    }

    public function updateReceipt($data, $client){
        try {
            Log::info('Fetching update receipts');
            $receipts = $this->receipts->where('client_id', $client)->first();
            $update = $receipts->update($data);
            return $update;
        } catch (Exception $e) {
            Log::error('Failed to get update receipts: ' . $e->getMessage());
            throw new Exception('Failed to get update receipts');
        }
    }

}
