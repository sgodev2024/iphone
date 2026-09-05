<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function __construct(private readonly BranchContext $branchContext) {}

    public function index()
    {
        return view('admin.account.index');
    }

    public function store(Request $request)
    {
        $credentials = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:accounts,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:accounts,id',
            'status' => 'nullable|in:1',
        ], [
            'code.required' => 'Vui lòng nhập :attribute.',
            'code.string' => ':attribute phải là chuỗi ký tự.',
            'code.max' => ':attribute không được vượt quá :max ký tự.',
            'code.unique' => ':attribute đã tồn tại trong hệ thống.',

            'name.required' => 'Vui lòng nhập :attribute.',
            'name.string' => ':attribute phải là chuỗi ký tự.',
            'name.max' => ':attribute không được vượt quá :max ký tự.',

            'parent_id.exists' => ':attribute không tồn tại.',

            'status.in' => ':attribute không hợp lệ.',
        ], [
            'code' => 'Mã tài khoản',
            'name' => 'Tên tài khoản',
            'parent_id' => 'Tài khoản cha',
            'status' => 'Trạng thái'
        ]);

        if ($credentials->fails()) {
            return response()->json([
                'message' => $credentials->errors()->first(),
            ], 422);
        }

        $credentials = $credentials->validate();

        $credentials['created_by'] = Auth::id();

        if (!empty($credentials['parent_id'])) {
            $parent  = Account::query()->findOrFail($credentials['parent_id']);
            $credentials['level'] = $parent->level + 1;
        }

        Account::create($credentials);

        return response()->json([
            'message' => 'Tạo mới tài khoản thành công.',
            'data' => $credentials
        ], 201);
    }

    public function update(Request $request)
    {
        $id = $request->id;

        $account = Account::findOrFail($id);

        $credentials = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:accounts,id|not_in:' . $account->id, // tránh chọn chính nó làm cha
            'status' => 'nullable|in:1',
        ], [
            'code.required' => 'Vui lòng nhập :attribute.',
            'code.string' => ':attribute phải là chuỗi ký tự.',
            'code.max' => ':attribute không được vượt quá :max ký tự.',
            'code.unique' => ':attribute đã tồn tại trong hệ thống.',

            'name.required' => 'Vui lòng nhập :attribute.',
            'name.string' => ':attribute phải là chuỗi ký tự.',
            'name.max' => ':attribute không được vượt quá :max ký tự.',

            'parent_id.exists' => ':attribute không tồn tại.',
            'parent_id.not_in' => ':attribute không được trùng với chính nó.',

            'status.in' => ':attribute không hợp lệ.',
        ], [
            'code' => 'Mã tài khoản',
            'name' => 'Tên tài khoản',
            'parent_id' => 'Tài khoản cha',
            'status' => 'Trạng thái'
        ]);

        if ($credentials->fails()) {
            return response()->json([
                'message' => $credentials->errors()->first(),
            ], 422);
        }

        $validated = $credentials->validate();

        $validated['status'] ??= 0;

        if (!empty($validated['parent_id'])) {
            $parent = Account::findOrFail($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
        } else {
            $validated['level'] = 1;
        }

        $account->update($validated);

        return response()->json([
            'message' => 'Cập nhật tài khoản thành công.',
            'data' => $account
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:accounts,id',
        ]);

        // Danh sách ID tài khoản mặc định (cố định, không được xoá)
        $accountDefaults = range(1, 235); // tương đương từ 1 đến 235

        // Kiểm tra nếu có ID nằm trong danh sách mặc định
        $intersect = array_intersect($request->ids, $accountDefaults);

        if (!empty($intersect)) {
            return response()->json([
                'message' => 'Không được phép xoá các tài khoản mặc định.',
                'protected_ids' => $intersect
            ], 422);
        }

        // Nếu không có tài khoản mặc định nào bị xoá → thực hiện xoá
        Account::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => 'Đã xoá các tài khoản kế toán đã chọn thành công.'
        ], 200);
    }

    private function sortAccountsHierarchically($accounts, $parentId = null, $level = 0)
    {
        $sorted = collect();

        foreach ($accounts->where('parent_id', $parentId) as $account) {
            $account->level_display = $level; // nếu cần thụt lề
            $sorted->push($account);
            $children = $this->sortAccountsHierarchically($accounts, $account->id, $level + 1);
            $sorted = $sorted->merge($children);
        }

        return $sorted;
    }

    public function list(Request $request)
    {
        $moneyAccounts = Account::query()
            ->with('creator')
            ->get();

        $orderedAccounts = $this->sortAccountsHierarchically($moneyAccounts);

        // Filter sau khi đã sort để giữ cấu trúc cây
        if ($request->filled('keyword')) {
            $keyword = mb_strtolower($request->input('keyword'));
            $orderedAccounts = $orderedAccounts->filter(function ($account) use ($keyword) {
                return str_contains(mb_strtolower($account->code), $keyword)
                    || str_contains(mb_strtolower($account->name), $keyword);
            });
        }

        return response()->json([
            'success' => true,
            'html' => view('admin.account.table_rows', compact('orderedAccounts'))->render(),
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $accounts = Account::where('code', 'like', "%$q%")
            ->orWhere('name', 'like', "%$q%")
            ->limit(10)
            ->get(['id', 'code', 'name']);

        return response()->json($accounts);
    }

    public function balance(Request $request)
    {
        if ($request->ajax()) {
            $dateRange = $request->input('dateRange');
            $searchInput = trim($request->input('searchInput'));

            if ($dateRange) {
                [$start, $end] = explode(' - ', $dateRange);
                $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            } else {
                $endDate = Carbon::now()->endOfDay();
                $startDate = $endDate->copy()->subMonth()->startOfDay();
            }

            $actor = $request->user();
            $globalBranchScope = $this->branchContext->isGlobal($actor);
            $branchId = $globalBranchScope ? null : $this->branchContext->branchId($actor);
            $branchAware = Schema::hasColumn('transactions', 'branch_id');
            $ownerIds = $globalBranchScope && $branchAware
                ? []
                : $this->ownerUserIds($actor);
            $ownerJoin = $ownerIds === []
                ? ''
                : 'AND t.user_id IN ('.implode(',', array_fill(0, count($ownerIds), '?')).')';
            $branchJoin = $branchAware
                ? 'AND (? = 1 OR t.branch_id = ?)'
                : '';

            $query = "
                WITH RECURSIVE account_tree AS (
                    SELECT ma.id, ma.code, ma.name, ma.parent_id, ma.level, CAST(ma.code AS CHAR(255)) AS path
                    FROM accounts ma
                    WHERE ma.parent_id IS NULL

                    UNION ALL

                    SELECT child.id, child.code, child.name, child.parent_id, child.level, CONCAT(parent.path, '-', child.code) AS path
                    FROM accounts child
                    JOIN account_tree parent ON child.parent_id = parent.id
                )
                SELECT
                    at.id AS account_id,
                    at.code AS account_code,
                    at.name AS account_name,
                    at.level,
                    at.path,

                    GREATEST(COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.debit_amount ELSE 0 END),0),0) AS opening_debit,
                    GREATEST(COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.credit_amount ELSE 0 END),0),0) AS opening_credit,
                    -- Phát sinh trong kỳ (Ghi Nợ)
                        COALESCE(SUM(
                            CASE WHEN t.transaction_date BETWEEN ? AND ?
                            THEN te.debit_amount ELSE 0 END
                        ),0) AS period_debit,

                        -- Phát sinh trong kỳ (Ghi Có)
                        COALESCE(SUM(
                             CASE WHEN t.transaction_date BETWEEN ? AND ?
                            THEN te.credit_amount ELSE 0 END
                        ),0) AS period_credit,
                    GREATEST((
                        COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.debit_amount ELSE 0 END),0)
                        + COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN te.debit_amount ELSE 0 END),0)
                        - COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.credit_amount ELSE 0 END),0)
                        - COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN te.credit_amount ELSE 0 END),0)
                    ),0) AS closing_balance_debit,

                     GREATEST((
                        COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.credit_amount ELSE 0 END),0)
                        + COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN te.credit_amount ELSE 0 END),0)
                        - COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.debit_amount ELSE 0 END),0)
                        - COALESCE(SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? THEN te.debit_amount ELSE 0 END),0)
                    ),0) AS closing_balance_credit

                FROM account_tree at
                    LEFT JOIN transaction_entries te
                        ON te.account_id = at.id
                        AND te.tableable_type IS NULL
                        AND te.tableable_id IS NULL
                    LEFT JOIN transactions t
                        ON t.id = te.transaction_id
                        AND t.type != 'other'
                        {$ownerJoin}
                        {$branchJoin}
                WHERE (at.code LIKE ? OR at.name LIKE ?)
                GROUP BY at.id, at.code, at.name, at.level, at.path
                ORDER BY at.path
                ";

            $bindings = [
                $startDate,
                $startDate,
                $startDate,
                $endDate,
                $startDate,
                $endDate,
                $startDate,
                $startDate,
                $endDate,
                $startDate,
                $startDate,
                $endDate,
                $startDate,
                $startDate,
                $endDate,
                $startDate,
                $startDate,
                $endDate,
                ...$ownerIds,
            ];

            if ($branchJoin !== '') {
                $bindings[] = $globalBranchScope ? 1 : 0;
                $bindings[] = $branchId;
            }

            $bindings[] = "%{$searchInput}%";
            $bindings[] = "%{$searchInput}%";

            $accounts = DB::select($query, $bindings);

            return response()->json([
                'success' => true,
                'html' => view('admin.account.balance_table', compact('accounts'))->render()
            ]);
        }

        return view('admin.account.balance');
    }

    /** @return list<int> */
    private function ownerUserIds(User $actor): array
    {
        $ownerId = (int) $actor->owner()->id;

        return User::query()
            ->whereKey($ownerId)
            ->orWhere('manager_id', $ownerId)
            ->pluck('id')
            ->push($actor->id)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
