<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmployeeDeletionService
{
    /**
     * Every audited business or historical reference to users.id.
     *
     * @var array<int, array{table: string, columns: array<int, string>, message: string}>
     */
    private const BUSINESS_REFERENCES = [
        ['table' => 'accounts', 'columns' => ['created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu kế toán.'],
        ['table' => 'bank_vouchers', 'columns' => ['owner_id', 'created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh phiếu thu/chi ngân hàng.'],
        ['table' => 'branches', 'columns' => ['user_id', 'admin_store_user_id'], 'message' => 'Không thể xóa nhân viên vì đang được liên kết với chi nhánh.'],
        ['table' => 'cash_vouchers', 'columns' => ['owner_id', 'created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh phiếu thu/chi tiền mặt.'],
        ['table' => 'check_inventory', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu kiểm kho.'],
        ['table' => 'clients', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu khách hàng.'],
        ['table' => 'companies', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu nhà cung cấp.'],
        ['table' => 'customer_debt_collections', 'columns' => ['owner_id', 'created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh thu công nợ khách hàng.'],
        ['table' => 'customer_debt_snapshot_states', 'columns' => ['owner_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ khách hàng.'],
        ['table' => 'customer_debt_yearly_snapshots', 'columns' => ['owner_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ khách hàng.'],
        ['table' => 'import_coupon', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh phiếu nhập hàng.'],
        ['table' => 'order_returns', 'columns' => ['user_id', 'created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu trả hàng.'],
        ['table' => 'orders', 'columns' => ['user_id', 'created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh đơn hàng.'],
        ['table' => 'product_imeis', 'columns' => ['deleted_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh lịch sử IMEI.'],
        ['table' => 'products', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu sản phẩm.'],
        ['table' => 'sgo_campaign_details', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu chiến dịch.'],
        ['table' => 'sgo_transactions', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh giao dịch tài chính.'],
        ['table' => 'storages', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đang được liên kết với kho hàng.'],
        ['table' => 'supplier_debt_snapshot_states', 'columns' => ['owner_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ nhà cung cấp.'],
        ['table' => 'supplier_debt_yearly_snapshots', 'columns' => ['owner_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu công nợ nhà cung cấp.'],
        ['table' => 'transactions', 'columns' => ['user_id', 'created_by'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh giao dịch tài chính.'],
        ['table' => 'user_wallet', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu ví hoặc tài chính.'],
        ['table' => 'warehouse', 'columns' => ['user_id'], 'message' => 'Không thể xóa nhân viên vì đã phát sinh dữ liệu kho.'],
    ];

    public function delete(User $actor, User $employee): void
    {
        $this->authorizeActor($actor);

        DB::transaction(function () use ($actor, $employee): void {
            $lockedEmployee = User::query()
                ->with('role')
                ->lockForUpdate()
                ->findOrFail($employee->getKey());

            $this->authorizeTarget($actor, $lockedEmployee);
            $this->ensureNoBusinessReferences($lockedEmployee);
            $this->deleteTechnicalData($lockedEmployee);

            if (! $lockedEmployee->delete()) {
                throw new DomainException('Không thể xóa nhân viên. Vui lòng kiểm tra dữ liệu liên quan.');
            }
        }, 3);
    }

    private function authorizeActor(User $actor): void
    {
        abort_unless(
            $actor->isAdministrator() || $actor->isAdminStore(),
            Response::HTTP_FORBIDDEN
        );
    }

    private function authorizeTarget(User $actor, User $employee): void
    {
        if ((int) $actor->getKey() === (int) $employee->getKey()) {
            throw ValidationException::withMessages([
                'employee' => ['Không thể xóa chính tài khoản đang đăng nhập.'],
            ]);
        }

        if (! $employee->isStaff()) {
            throw ValidationException::withMessages([
                'employee' => ['Chỉ có thể xóa tài khoản nhân viên.'],
            ]);
        }

        if (! $actor->isAdminStore()) {
            return;
        }

        abort_if($actor->branch_id === null, Response::HTTP_FORBIDDEN);
        abort_if(
            $employee->branch_id === null || (int) $employee->branch_id !== (int) $actor->branch_id,
            Response::HTTP_NOT_FOUND
        );
    }

    private function ensureNoBusinessReferences(User $employee): void
    {
        foreach (self::BUSINESS_REFERENCES as $reference) {
            if (! Schema::hasTable($reference['table'])) {
                continue;
            }

            foreach ($reference['columns'] as $column) {
                if (! Schema::hasColumn($reference['table'], $column)) {
                    continue;
                }

                if (DB::table($reference['table'])->where($column, $employee->getKey())->exists()) {
                    throw new DomainException($reference['message']);
                }
            }
        }
    }

    private function deleteTechnicalData(User $employee): void
    {
        foreach (['carts', 'config', 'user_info'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $employee->getKey())->delete();
            }
        }

        $this->deletePolymorphicTechnicalData(
            'personal_access_tokens',
            'tokenable',
            (int) $employee->getKey()
        );
        $this->deletePolymorphicTechnicalData(
            'notifications',
            'notifiable',
            (int) $employee->getKey()
        );

        foreach (['sessions', 'oauth_access_tokens', 'oauth_auth_codes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $employee->getKey())->delete();
            }
        }

        User::query()
            ->where('manager_id', $employee->getKey())
            ->update(['manager_id' => null]);
    }

    private function deletePolymorphicTechnicalData(string $table, string $prefix, int $employeeId): void
    {
        $idColumn = "{$prefix}_id";
        $typeColumn = "{$prefix}_type";

        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $idColumn)
            || ! Schema::hasColumn($table, $typeColumn)) {
            return;
        }

        DB::table($table)
            ->where($idColumn, $employeeId)
            ->where($typeColumn, User::class)
            ->delete();
    }
}
