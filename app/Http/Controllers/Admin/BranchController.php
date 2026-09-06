<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Roles;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdministrator();

        $searchText = $request->input('s');

        $branchs = Branch::query()
            ->with(['adminStore', 'storages'])
            ->when(! empty($searchText), function (Builder $query) use ($searchText) {
                $query->where('name', 'like', "%{$searchText}%");
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.branch.table', compact('branchs'))->render(),
            ], Response::HTTP_OK);
        }

        $adminStoreUsers = collect();

        if (Auth::user()?->isAdministrator()) {
            $adminStoreUsers = User::query()
                ->whereIn('role_id', Roles::adminStoreIds())
                ->whereNull('branch_id')
                ->whereDoesntHave('administeredBranch')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        return view('admin.branch.index', compact('branchs', 'adminStoreUsers'));
    }

    public function create(Request $request)
    {
        return $this->index($request);
    }

    public function show(string $id)
    {
        $this->ensureAdministrator();

        $branch = Branch::query()
            ->with('adminStore')
            ->find($id);

        if (! $branch) {
            return response()->json([
                'message' => 'Du lieu khong ton tai tren he thong.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $branch,
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $this->ensureAdministrator();
        DB::transaction(function () use ($request): void {
            $data = $this->validateBranch($request);
            $adminStore = $this->lockAdminStoreForBranch((int) $data['admin_store_user_id']);
            $data['user_id'] = Auth::id();
            $data['manager_name'] = $adminStore->name;
            $branch = Branch::create($data);
            $this->syncAdminStoreAssignment($branch, $adminStore);
            $this->createDefaultStorage($branch, $adminStore);
        }, 3);
        return response()->json(['message' => 'Tao chi nhanh thanh cong.'], Response::HTTP_CREATED);
    }
    public function update(Request $request, $id)
    {
        $this->ensureAdministrator();
        DB::transaction(function () use ($request, $id): void {
            $branch = Branch::query()->lockForUpdate()->find($id);
            if (! $branch) abort(Response::HTTP_NOT_FOUND);
            $data = $this->validateBranch($request, $branch->id);
            $previous = $branch->admin_store_user_id ? (int) $branch->admin_store_user_id : null;
            $adminStore = $this->lockAdminStoreForBranch((int) $data['admin_store_user_id'], $branch->id);
            $data['manager_name'] = $adminStore->name;
            $branch->update($data);
            $this->syncAdminStoreAssignment($branch, $adminStore, $previous);
        }, 3);
        return response()->json(['message' => 'Cap nhat chi nhanh thanh cong.'], Response::HTTP_OK);
    }
    public function destroy(Branch $branch)
    {
        $this->ensureAdministrator();

        return $this->deleteBranchesWithResponse(
            [$branch->id],
            'Xóa chi nhánh thành công.',
        );
    }

    public function bulkDestroy(Request $request)
    {
        $this->ensureAdministrator();

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('branches', 'id'),
            ],
        ], __('request.messages'));

        return $this->deleteBranchesWithResponse(
            array_values(array_unique($validated['ids'])),
            'Xóa các chi nhánh đã chọn thành công.',
        );
    }

    public function changeStatus(Request $request)
    {
        $this->ensureAdministrator();
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('branches', 'id'),
            ],
        ]);

        Branch::query()
            ->whereIn('id', $validated['ids'])
            ->each(function (Branch $branch) {
                $branch->update(['status' => ! $branch->status]);
            });

        return response()->json([
            'message' => 'Thay doi trang thai thanh cong.',
        ], Response::HTTP_OK);
    }

    private function syncAdminStoreAssignment(Branch $branch, User $adminStore, ?int $previous = null): void
    {
        if ($previous !== null && $previous !== $adminStore->id) User::query()->whereKey($previous)->where('branch_id', $branch->id)->update(['branch_id' => null]);
        User::query()->whereKey($adminStore->id)->update(['branch_id' => $branch->id]);
    }

    private function validateBranch(Request $request, $id = null): array
    {
        return $request->validate(['name' => ['required','string','max:255',Rule::unique('branches','name')->ignore($id)], 'admin_store_user_id' => ['required','integer',Rule::exists('users','id')->where(fn ($q) => $q->whereIn('role_id',Roles::adminStoreIds())),Rule::unique('branches','admin_store_user_id')->ignore($id)], 'address' => ['required','string','max:500'], 'phone' => ['nullable','string','regex:/^0[0-9]{9}$/'], 'email' => ['nullable','email','max:255'], 'status' => ['required','in:0,1']], __('request.messages'));
    }

    private function ensureAdministrator(): void { abort_unless(Auth::user()?->isAdministrator(), Response::HTTP_FORBIDDEN); }
    private function lockAdminStoreForBranch(int $id, ?int $branchId = null): User
    {
        $user = User::query()->whereKey($id)->whereIn('role_id',Roles::adminStoreIds())->lockForUpdate()->first();
        if (! $user) throw ValidationException::withMessages(['admin_store_user_id' => ['Tai khoan duoc chon khong phai la Admin Store.']]);
        $managed = Branch::query()->where('admin_store_user_id',$user->id)->lockForUpdate()->first();
        if (($managed && $managed->id !== $branchId) || ($user->branch_id !== null && (int) $user->branch_id !== $branchId)) throw ValidationException::withMessages(['admin_store_user_id' => ['Admin Store da duoc gan cho cua hang khac.']]);
        return $user;
    }
    private function createDefaultStorage(Branch $branch, User $user): Storage { return Storage::create(['user_id'=>$user->id,'branch_id'=>$branch->id,'name'=>"Kho {$branch->name}",'location'=>$branch->address]); }
    private function ensureBranchCanBeDeleted(Branch $branch): void
    {
        $staff = User::query()->where('branch_id', $branch->id)->where('id', '<>', $branch->admin_store_user_id)->exists();
        if ($staff) {
            throw ValidationException::withMessages([
                'branch' => ['Không thể xóa chi nhánh vì đang có nhân viên.'],
            ]);
        }

        $businessReferences = [
            'orders' => 'Không thể xóa chi nhánh vì đang có đơn hàng.',
            'clients' => 'Không thể xóa chi nhánh vì đang có khách hàng.',
            'companies' => 'Không thể xóa chi nhánh vì đang có nhà cung cấp.',
            'order_returns' => 'Không thể xóa chi nhánh vì đang có phiếu trả hàng.',
            'transactions' => 'Không thể xóa chi nhánh vì đang có giao dịch tài chính.',
            'cash_vouchers' => 'Không thể xóa chi nhánh vì đang có phiếu thu/chi tiền mặt.',
            'bank_vouchers' => 'Không thể xóa chi nhánh vì đang có phiếu thu/chi ngân hàng.',
            'customer_debt_collections' => 'Không thể xóa chi nhánh vì đang có phiếu thu công nợ khách hàng.',
            'customer_debt_yearly_snapshots' => 'Không thể xóa chi nhánh vì đang có dữ liệu công nợ khách hàng.',
            'customer_debt_snapshot_states' => 'Không thể xóa chi nhánh vì đang có trạng thái công nợ khách hàng.',
            'supplier_debt_yearly_snapshots' => 'Không thể xóa chi nhánh vì đang có dữ liệu công nợ nhà cung cấp.',
            'supplier_debt_snapshot_states' => 'Không thể xóa chi nhánh vì đang có trạng thái công nợ nhà cung cấp.',
            'supplier_debts' => 'Không thể xóa chi nhánh vì đang có công nợ nhà cung cấp.',
        ];

        foreach ($businessReferences as $table => $message) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id') && DB::table($table)->where('branch_id', $branch->id)->exists()) {
                throw ValidationException::withMessages([
                    'branch' => [$message],
                ]);
            }
        }
    }

    private function deleteRemovableDefaultStorage(Branch $branch): void
    {
        $storages = Storage::query()->where('branch_id', $branch->id)->lockForUpdate()->get();
        if ($storages->isEmpty()) {
            return;
        }
        if ($storages->count() !== 1) {
            throw ValidationException::withMessages([
                'branch' => ['Không thể xóa chi nhánh vì đang có nhiều kho liên quan.'],
            ]);
        }
        $storage = $storages->first();
        $storageReferences = [
            'users' => 'Không thể xóa chi nhánh vì kho đang được gán cho nhân viên.',
            'product_storage' => 'Không thể xóa chi nhánh vì kho vẫn còn tồn kho.',
            'product_imeis' => 'Không thể xóa chi nhánh vì kho vẫn còn IMEI.',
            'import_coupon' => 'Không thể xóa chi nhánh vì kho đang có phiếu nhập hàng.',
            'order_details' => 'Không thể xóa chi nhánh vì kho đang có dữ liệu đơn hàng.',
            'order_return_details' => 'Không thể xóa chi nhánh vì kho đang có dữ liệu trả hàng.',
        ];
        foreach ($storageReferences as $table => $message) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'storage_id') && DB::table($table)->where('storage_id', $storage->id)->exists()) {
                throw ValidationException::withMessages([
                    'branch' => [$message],
                ]);
            }
        }
        $storage->delete();
    }

    private function deleteBranchesWithResponse(array $ids, string $successMessage)
    {
        try {
            $this->deleteBranches($ids);

            return response()->json([
                'message' => $successMessage,
            ], Response::HTTP_OK);
        } catch (ValidationException | HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Không thể xóa chi nhánh.', [
                'branch_ids' => $ids,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Không thể xóa chi nhánh. Vui lòng kiểm tra dữ liệu liên quan.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function deleteBranches(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            $branches = Branch::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_unless($branches->count() === count($ids), Response::HTTP_NOT_FOUND);

            foreach ($branches as $branch) {
                $this->ensureBranchCanBeDeleted($branch);
                $this->deleteRemovableDefaultStorage($branch);
                $this->clearAdminStoreAssignment($branch);
                $branch->delete();
            }
        }, 3);
    }

    private function clearAdminStoreAssignment(Branch $branch): void
    {
        User::query()
            ->whereKey($branch->admin_store_user_id)
            ->where('branch_id', $branch->id)
            ->update(['branch_id' => null]);
    }
}
