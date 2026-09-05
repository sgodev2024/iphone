<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\BranchContext;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TransactionService
{
    protected $transaction, $user;
    private BranchContext $branchContext;

    public function __construct(
        Transaction $transaction,
        User $user,
        ?BranchContext $branchContext = null
    )
    {
        $this->transaction = $transaction;
        $this->user = $user;
        $this->branchContext = $branchContext ?? app(BranchContext::class);
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

    public function getPaginatedTransactionsForAdmin(User $actor, $status, $startDate, $endDate)
    {
        try {
            $queryBuilder = $this->transaction->newQuery()->with('user');
            if (! $this->branchContext->isGlobal($actor)) {
                $queryBuilder->whereIn('user_id', $this->ownerUserIds($actor));
            }
            $this->branchContext->scope($queryBuilder, $actor);

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

    public function createNewTransaction(array $data, User|int $actor)
    {
        DB::beginTransaction();
        $amount = preg_replace('/[^\d]/', '', $data['amount']);
        $branchAware = Schema::hasColumn('transactions', 'branch_id');
        if (! $actor instanceof User && $branchAware) {
            throw new \LogicException('A User actor is required for Branch-scoped transaction writes.');
        }
        $actorId = $actor instanceof User ? (int) $actor->id : (int) $actor;
        $branchId = $branchAware
            ? $this->branchContext->resolveWriteBranch(
                $actor,
                isset($data['branch_id']) ? (int) $data['branch_id'] : null
            )
            : null;
        try {
            $attributes = [
                'amount' => $amount,
                'status' => Transaction::STATUS_COMPLETED,
                'user_id' => $actorId,
                'notification' => 1,
                'description' => $data['description'],
            ];
            if ($branchAware) {
                $attributes['branch_id'] = $branchId;
            }
            $transaction = $this->transaction->create($attributes);


            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create new Transaction: ' . $e->getMessage());
            throw new Exception('Failed to create new transaction');
        }
    }

    public function getTransactionById(User|int $actor, $id = null)
    {
        try {
            if (! $actor instanceof User) {
                if (Schema::hasColumn('transactions', 'branch_id')) {
                    throw new \LogicException('A User actor is required for Branch-scoped transaction reads.');
                }
                $id = $actor;
                return $this->transaction->findOrFail($id);
            }
            $query = $this->transaction->newQuery();
            if (! $this->branchContext->isGlobal($actor)) {
                $query->whereIn('user_id', $this->ownerUserIds($actor));
            }
            $this->branchContext->scope($query, $actor);

            return $query->findOrFail($id);
        } catch (Exception $e) {
            Log::error("Failed to find this transaction: " . $e->getMessage());
            throw new Exception("Failed to find this transaction");
        }
    }

    public function confirmTransaction(User|int $actor, $id = null)
    {
        try {
            $transaction = $this->getTransactionById($actor, $id);
            $transaction->status = Transaction::STATUS_COMPLETED;
            $transaction->notification = 2;
            $transaction->save();
            return $transaction;
        } catch (Exception $e) {
            Log::error('Failed to confirm transaction: ' . $e->getMessage());
            throw new Exception("Failed to confirm transaction");
        }
    }

    public function rejectTransaction(User|int $actor, $id = null)
    {
        try {
            $transaction = $this->getTransactionById($actor, $id);
            $transaction->status = Transaction::STATUS_FAILED;
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

    public function getTransactionNotificationForAdmin(User $actor)
    {
        try {
            $query = $this->transaction->newQuery()
                ->orderByDesc('created_at')
                ->where('notification', 2);
            if (! $this->branchContext->isGlobal($actor)) {
                $query->whereIn('user_id', $this->ownerUserIds($actor));
            }
            $this->branchContext->scope($query, $actor);

            return $query;
        } catch (Exception $e) {
            Log::error("Failed to get transaction notification for admin: " . $e->getMessage());
            throw new Exception("Failed to get trasaction notification for admin");
        }
    }

    private function ownerUserIds(User $actor): array
    {
        $ownerId = (int) $actor->ownerId();

        return User::query()
            ->whereKey($ownerId)
            ->orWhere('manager_id', $ownerId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->push((int) $actor->id)
            ->unique()
            ->values()
            ->all();
    }
}
