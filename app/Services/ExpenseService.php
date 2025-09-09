<?php

namespace App\Services;

use App\Models\Expense;
use Exception;
use Illuminate\Support\Facades\Log;

class ExpenseService
{

    protected $expense;
    public function __construct(Expense $expense){
        $this->expense = $expense;
    }

    public function getAllExpense(){
        try {
            return $this->expense->get();
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

    public function findExpenseByCompany( $supplier){
        try {
            Log::info('Fetching find Expense');
            $expenses = $this->expense->where('companies_id', $supplier)->first();
            return $expenses;
        } catch (Exception $e) {
            Log::error('Failed to get find expense: ' . $e->getMessage());
            throw new Exception('Failed to get find expense');
        }
    }
    public function findExpenseById($id){
        try {
            Log::info('Fetching find expense ');
            $receipt = $this->expense->find($id);
            return $receipt;
        } catch (Exception $e) {
            Log::error('Failed to  find expense: ' . $e->getMessage());
            throw new Exception('Failed to find expense ');
        }
    }


}
