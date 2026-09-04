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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $searchText = $request->input('s');

        $branchs = Branch::query()
            ->with(['adminStore', 'storages'])
            ->where('user_id', Auth::id())
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
        $branch = Branch::query()
            ->with('adminStore')
            ->where('user_id', Auth::id())
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
            $branch = Branch::query()->where('user_id', Auth::id())->lockForUpdate()->find($id);
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
    public function destroy(Request $request)
    {
        $this->ensureAdministrator();
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $branches = Branch::query()->where('user_id', Auth::id())->whereIn('id', $validated['ids'])->lockForUpdate()->get();
            foreach ($branches as $branch) {
                $this->ensureBranchCanBeDeleted($branch);
                $this->deleteRemovableDefaultStorage($branch);
                $this->clearAdminStoreAssignment($branch);
                $branch->delete();
            }
        });

        return response()->json([
            'message' => 'Xoa chi nhanh thanh cong.',
        ], Response::HTTP_OK);
    }

    public function changeStatus(Request $request)
    {
        $this->ensureAdministrator();
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
        ]);

        Branch::query()
            ->where('user_id', Auth::id())
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
        return $request->validate(['name' => ['required','string','max:255',Rule::unique('branches','name')->where(fn ($q) => $q->where('user_id',Auth::id()))->ignore($id)], 'admin_store_user_id' => ['required','integer',Rule::exists('users','id')->where(fn ($q) => $q->whereIn('role_id',Roles::adminStoreIds())),Rule::unique('branches','admin_store_user_id')->ignore($id)], 'address' => ['required','string','max:500'], 'phone' => ['nullable','string','regex:/^0[0-9]{9}$/'], 'email' => ['nullable','email','max:255'], 'status' => ['required','in:0,1']], __('request.messages'));
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
        if ($staff) throw ValidationException::withMessages(['ids' => ['Khong the xoa chi nhanh vi da co nhan vien lien quan.']]);
        foreach (['orders' => 'don hang', 'clients' => 'khach hang', 'companies' => 'nha cung cap', 'order_returns' => 'tra hang'] as $table => $label) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id') && DB::table($table)->where('branch_id', $branch->id)->exists()) {
                throw ValidationException::withMessages(['ids' => ["Khong the xoa chi nhanh vi da co du lieu {$label} lien quan."]]);
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
            throw ValidationException::withMessages(['ids' => ['Khong the xoa chi nhanh vi co nhieu kho lien quan.']]);
        }
        $storage = $storages->first();
        foreach (['users', 'product_storage', 'product_imeis', 'import_coupon', 'order_details', 'order_return_details'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'storage_id') && DB::table($table)->where('storage_id', $storage->id)->exists()) {
                throw ValidationException::withMessages(['ids' => ['Khong the xoa chi nhanh vi kho mac dinh da co ton kho hoac giao dich.']]);
            }
        }
        $storage->delete();
    }

    private function clearAdminStoreAssignment(Branch $branch): void
    {
        User::query()
            ->whereKey($branch->admin_store_user_id)
            ->where('branch_id', $branch->id)
            ->update(['branch_id' => null]);
    }
}
