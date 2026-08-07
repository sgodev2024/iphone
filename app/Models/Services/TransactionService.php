<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    protected $transaction, $user;

    public function __construct(Transaction $transaction, User $user)
    {
        $this->transaction = $transaction;
        $this->user = $user;
    }

    public function getPaginatedTransactionsForSuperAdmin($query, $startDate, $endDate, $status)
    {
        try {
            $queryBuilder = Transaction::with('user');

            if ($query) {
                $queryBuilder->whereHas('user', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                });
            }

            if ($status) {
                $queryBuilder->where('status', $status);
            }

            if ($startDate) {
                $queryBuilder->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $queryBuilder->whereDate('created_at', '<=', $endDate);
            }

            $transactions = $queryBuilder->orderByDesc('created_at')->paginate(10);

            return $transactions;
        } catch (Exception $e) {
            Log::error("Failed to get paginated transaction for super admin: " . $e->getMessage());
            throw new Exception('Failed to get paginated transaction for super admin');
        }
    }

    public function getPaginatedTransactionsForAdmin($id, $status, $startDate, $endDate)
    {
        try {
            $queryBuilder = $this->transaction->where('user_id', $id);

            if ($status) {
                $queryBuilder->where('status', $status);
            }

            if ($startDate) {
                $queryBuilder->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $queryBuilder->whereDate('created_at', '<=', $endDate);
            }

            $transactions = $queryBuilder->orderByDesc('created_at')->paginate(10);
            return $transactions;
        } catch (Exception $e) {
            Log::error('Failed to get paginated for admin: ' . $e->getMessage());
            throw new Exception(("Failed to get paginated transaction for admin"));
        }
    }

    public function createNewTransaction(array $data, $userId)
    {
        DB::beginTransaction();
        $amount = preg_replace('/[^\d]/', '', $data['amount']);
        try {
            $transaction = $this->transaction->create([
                'amount' => $amount,
                'status' => 1,
                'user_id' => $userId,
                'notification' => 1,
                'description' => $data['description'],
            ]);


            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create new Transaction: ' . $e->getMessage());
            throw new Exception('Failed to create new transaction');
        }
    }

    public function getTransactionById($id)
    {
        try {
            return $this->transaction->find($id);
        } catch (Exception $e) {
            Log::error("Failed to find this transaction: " . $e->getMessage());
            throw new Exception("Failed to find this transaction");
        }
    }

    public function confirmTransaction($id)
    {
        try {
            $transaction = $this->transaction->find($id);
            $transaction->status = 1;
            $transaction->notification = 2;
            $transaction->save();
            return $transaction;
        } catch (Exception $e) {
            Log::error('Failed to confirm transaction: ' . $e->getMessage());
            throw new Exception("Failed to confirm transaction");
        }
    }

    public function rejectTransaction($id)
    {
        try {
            $transaction = $this->transaction->find($id);
            $transaction->status = 2;
            $transaction->notification = 2;
            $transaction->save();
            return $transaction;
        } catch (Exception $e) {
            Log::error('Failed to reject transaction: ' . $e->getMessage());
            throw new Exception("Failed to reject transaction");
        }
    }

    public function getTransactionNotificationForSuperAdmin()
    {
        try {
            return $this->transaction->orderByDesc('created_at')->where('notification', 1)->get();
        } catch (Exception $e) {
            Log::error('Failed to get transaction notification for super admin: ' . $e->getMessage());
            throw new Exception("Failed to get transaction notification for super admin");
        }
    }

    public function getTransactionNotificationForAdmin()
    {
        try {
            return $this->transaction->orderByDesc('created_at')->where('notification', 2);
        } catch (Exception $e) {
            Log::error("Failed to get transaction notification for admin: " . $e->getMessage());
            throw new Exception("Failed to get trasaction notification for admin");
        }
    }
}
