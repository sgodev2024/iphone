<?php

namespace App\Services;

use App\Models\CheckInventory;
use App\Models\User;
use App\Models\warehome;
use App\Support\BranchContext;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;



/**
 * Summary of CheckInventory
 */
class CheckInventoryService
{

    protected $checkInventory;

    public function __construct(
        CheckInventory $checkInventory,
        private readonly BranchContext $branchContext
    )
    {
        $this->checkInventory = $checkInventory;
    }

    public function getAllCheckInventory(User $user): LengthAwarePaginator
    {

        try {
            $query = $this->checkInventory->newQuery();
            $this->scopeForUser($query, $user);
            $checkInventory = $query->orderByDesc('created_at')->paginate(10);
            return $checkInventory;
        } catch (HttpExceptionInterface $e) {
            throw $e;
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
    public function filterCheck($startDate, $endDate, $phone, User $user)
    {
        try {
            $query = $this->checkInventory->query();
            $this->scopeForUser($query, $user);

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
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to find check tickets: ' . $e->getMessage());
            throw new Exception('Failed to find check tickets');
        }
    }
    public function getCheckInventoryById($id, User $user)
    {

        try {
            $query = $this->checkInventory->newQuery();
            $this->scopeForUser($query, $user);
            $checkInventory = $query->findOrFail($id);
            return $checkInventory;
        } catch (ModelNotFoundException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to get  checkInventory by ' . $id . '-' . $e->getMessage());
            throw new Exception('Failed to get  checkInventory by ' . $id);
        }
    }

    public function addCheckInventory(array $data, ?Collection $warehomes = null)
    {
        try {
            $slTang = 0;
            $slGiam = 0;
            $warehomes ??= warehome::query()
                ->where('user_id', $data['user_id'])
                ->whereNotNull('reality')
                ->get();
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

    private function scopeForUser(Builder $query, User $user): Builder
    {
        if ($this->branchContext->isGlobal($user)) {
            return $query;
        }

        $branchId = $this->branchContext->branchId($user);

        if ($user->isStaff()) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereHas('user', function (Builder $userQuery) use ($branchId): void {
            $userQuery->where('branch_id', $branchId);
        });
    }
}
