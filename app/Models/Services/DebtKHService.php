<?php

namespace App\Services;

use App\Models\ClientDebt;
use Exception;
use Illuminate\Support\Facades\Log;

class DebtKHService
{
    protected $clientDebt;
    public function __construct(ClientDebt $clientDebt){
        $this->clientDebt = $clientDebt;
    }

    public function getAllClientDebt(){
        try {
            Log::info('Fetching all clientDebt');
            return $this->clientDebt->orderByDesc('created_at')->get();
        } catch (Exception $e) {
            Log::error('Failed to fetch clientDebt: ' . $e->getMessage());
            throw new Exception('Failed to fetch clientDebt');
        }
    }

    public function addClientDebt($data){
        try {
            Log::info('Fetching add clientDebt');
            $receipts = $this->clientDebt->create($data);
            return $receipts;
        } catch (Exception $e) {
            Log::error('Failed to  add clientDebt: ' . $e->getMessage());
            throw new Exception('Failed to add clientDebt');

        }
    }

    public function updateClientDebt($data, $client_id){
        try {
            Log::info('Fetching update clientDebt');
            $receipt = $this->clientDebt->where('client_id', $client_id)->first();
            $update = $receipt->update($data);
            return $update;
        } catch (Exception $e) {
            Log::error('Failed to  update clientDebt: ' . $e->getMessage());
            throw new Exception('Failed to update clientDebt');
        }
    }

    public function findClientDebtByClient($client_id){
        try {
            Log::info('Fetching find clientDebt');
            $receipt = $this->clientDebt->where('client_id', $client_id)->first();
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find clientDebt: ' . $e->getMessage());
            throw new Exception('Failed to find clientDebt');
        }
    }

    public function delete($client_id){
        try {
            Log::info('Fetching delete clientDebt');
            $receipt = $this->clientDebt->where('client_id', $client_id)->first();
            return $receipt->delete();
        } catch (Exception $e) {
            Log::error('Failed to  delete clientDebt: ' . $e->getMessage());
            throw new Exception('Failed to delete clientDebt');
        }
    }

    public function findClientDebtById($id){
        try {
            Log::info('Fetching find clientDebt');
            $receipt = $this->clientDebt->find($id);
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find clientDebt: ' . $e->getMessage());
            throw new Exception('Failed to find clientDebt');
        }
    }

}
