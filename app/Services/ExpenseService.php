<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Expense;
use Exception;
use Illuminate\Support\Facades\Log;

class ExpenseService
{

    protected $expense;
    public function __construct(Expense $expense){
        $this->expense = $expense;
    }

    public function getAllExpense(?int $ownerId = null){
        try {
            $query = $this->expense->newQuery();

            if ($ownerId !== null) {
                $query->whereIn('companies_id', Company::query()->where('user_id', $ownerId)->select('id'));
            }

            return $query->get();
        } catch (Exception $e) {
            Log::error('Failed to get all expense: ' . $e->getMessage());
            throw new Exception('Failed to get all expense');
        }
    }

    public function addExpense($data){
        try {
            Log::info('Fetching add Expense');
            $expense = $this->expense->create($data);
            return $expense;
        } catch (Exception $e) {
            Log::error('Failed to get add expense: ' . $e->getMessage());
            throw new Exception('Failed to get add expense');
        }
    }

    public function updateExpense($data, $supplier){
        try {
            Log::info('Fetching update Expense');
            $expense = $this->expense->where('companies_id', $supplier)->first();
            $update = $expense->update($data);
            return $update;
        } catch (Exception $e) {
            Log::error('Failed to get update expense: ' . $e->getMessage());
            throw new Exception('Failed to get update expense');
        }
    }

    public function findExpenseBysupplier( $supplier){
        try {
            Log::info('Fetching find Expense');
            $expenses = $this->expense->where('supplier_id', $supplier)->first();
            return $expenses;
        } catch (Exception $e) {
            Log::error('Failed to get find expense: ' . $e->getMessage());
            throw new Exception('Failed to get find expense');
        }
    }

    public function findExpenseByCompany($supplier, ?int $ownerId = null){
        try {
            Log::info('Fetching find Expense');
            $query = $this->expense->where('companies_id', $supplier);

            if ($ownerId !== null) {
                $query->whereIn('companies_id', Company::query()->where('user_id', $ownerId)->select('id'));
            }

            $expenses = $query->first();
            return $expenses;
        } catch (Exception $e) {
            Log::error('Failed to get find expense: ' . $e->getMessage());
            throw new Exception('Failed to get find expense');
        }
    }
    public function findExpenseById($id, ?int $ownerId = null){
        try {
            Log::info('Fetching find expense ');
            $query = $this->expense->newQuery()->whereKey($id);

            if ($ownerId !== null) {
                $query->whereIn('companies_id', Company::query()->where('user_id', $ownerId)->select('id'));
            }

            $receipt = $query->firstOrFail();
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find expense: ' . $e->getMessage());
            throw new Exception('Failed to find expense ');
        }
    }


}
