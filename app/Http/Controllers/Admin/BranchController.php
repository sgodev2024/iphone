<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $searchText = $request->input('s');

        $branchs = Branch::query()
            ->with('adminStore')
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
        DB::transaction(function () use ($request) {
            $credentials = $this->validateBranch($request);
            $credentials['user_id'] = Auth::id();
            $adminStoreId = $credentials['admin_store_user_id'] ?? null;

            if ($adminStoreId !== null) {
                $credentials['manager_name'] = User::query()->findOrFail($adminStoreId)->name;
            }

            $branch = Branch::create($credentials);
            $this->syncAdminStoreAssignment($branch, $adminStoreId);
        });

        return response()->json([
            'message' => 'Tao chi nhanh thanh cong.',
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::query()
            ->where('user_id', Auth::id())
            ->find($id);

        if (! $branch) {
            return response()->json([
                'message' => 'Chi nhanh khong ton tai hoac ban khong co quyen chinh sua.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($request, $branch, $id) {
            $credentials = $this->validateBranch($request, $id);
            $previousAdminStoreId = $branch->admin_store_user_id
                ? (int) $branch->admin_store_user_id
                : null;

            if (array_key_exists('admin_store_user_id', $credentials)
                && $credentials['admin_store_user_id'] !== null) {
                $credentials['manager_name'] = User::query()
                    ->findOrFail($credentials['admin_store_user_id'])
                    ->name;
            }

            $branch->update($credentials);

            if (array_key_exists('admin_store_user_id', $credentials)) {
                $this->syncAdminStoreAssignment($branch, $credentials['admin_store_user_id'], $previousAdminStoreId);
            }
        });

        return response()->json([
            'message' => 'Cap nhat chi nhanh thanh cong.',
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request)
    {
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
            ->delete();

        return response()->json([
            'message' => 'Xoa chi nhanh thanh cong.',
        ], Response::HTTP_OK);
    }

    public function changeStatus(Request $request)
    {
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

    private function syncAdminStoreAssignment(Branch $branch, ?int $adminStoreId, ?int $previousAdminStoreId = null): void
    {
        if ($previousAdminStoreId !== null && $previousAdminStoreId !== $adminStoreId) {
            User::query()
                ->whereKey($previousAdminStoreId)
                ->where('branch_id', $branch->id)
                ->update(['branch_id' => null]);
        }

        if ($adminStoreId !== null) {
            User::query()
                ->whereKey($adminStoreId)
                ->update(['branch_id' => $branch->id]);
        }
    }

    private function validateBranch(Request $request, $id = null): array
    {
        if ($request->exists('admin_store_user_id') && ! Auth::user()?->isAdministrator()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('branches', 'name')
                        ->where(fn ($query) => $query->where('user_id', Auth::id()))
                        ->ignore($id),
                ],
                'admin_store_user_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('users', 'id')
                        ->where(fn ($query) => $query
                            ->whereIn('role_id', Roles::adminStoreIds())
                            ->where(function ($query) use ($id) {
                                $query->whereNull('branch_id');

                                if ($id !== null) {
                                    $query->orWhere('branch_id', $id);
                                }
                            })),
                    Rule::unique('branches', 'admin_store_user_id')->ignore($id),
                ],
                'manager_name' => ['nullable', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'phone' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/'],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => ['required', 'in:0,1'],
            ],
            __('request.messages'),
            [
                'name' => 'Ten chi nhanh',
                'admin_store_user_id' => 'Admin Store',
                'manager_name' => 'Ten nguoi quan ly',
                'address' => 'Dia chi',
                'phone' => 'So dien thoai',
                'email' => 'Email',
                'status' => 'Trang thai',
            ]
        );
    }
}
