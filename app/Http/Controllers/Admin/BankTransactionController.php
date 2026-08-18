<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\MoneyAccount;
use App\Models\Receipt;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Services\CustomerDebtCollectionService;
use App\Services\BankActivityReadService;
use App\Services\SupplierPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BankTransactionController extends Controller
{
    public function index()
    {
        return view('admin.cash-bank.bank');
    }

    public function list(Request $request, BankActivityReadService $activityRead)
    {
        $dateRange = $request->query('date_range');

        if ($dateRange) {
            [$from, $to] = explode(' - ', $dateRange);
            $from = Carbon::createFromFormat('d/m/Y', trim($from))->toDateString();
            $to = Carbon::createFromFormat('d/m/Y', trim($to))->toDateString();
        } else {
            $from = now()->subMonth()->toDateString();
            $to = now()->toDateString();
        }

        // 👉 Lấy danh sách account_id của 112 và các con (ngân hàng)
        $bankAccountIds = DB::table('accounts')
            ->where(function ($q) {
                $q->where('code', '112')
                    ->orWhere('parent_id', function ($sub) {
                        $sub->select('id')->from('accounts')->where('code', '112')->limit(1);
                    });
            })
            ->pluck('id');

        $result = $activityRead->read(
            $request->user(),
            $this->transactionOwnerIds(),
            $bankAccountIds,
            $from,
            $to,
            max(1, (int) $request->query('page', 1))
        );

        $activities = $result['paginator'];
        $totals = $result['totals'];
        $type = 'bank';

        return response()->json([
            'success' => true,
            'html' => view('admin.cash-bank._table', compact('activities', 'totals', 'type'))->render(),
            'pagination' => view('admin.cash-bank._pagination', compact('activities'))->render(),
        ]);
    }

    public function save(
        Request $request,
        CustomerDebtCollectionService $collectionService,
        SupplierPaymentService $supplierPaymentService
    )
    {
        $type = 'bank';
        $transaction = null;
        $mainEntry = null;
        $contraEntry = null;

        $transactionId = $request->input('transactionId', null);

        // Lấy danh sách tài khoản ngân hàng (con của 112)
        $moneyAccounts = Account::query()
            ->whereHas('parent', function ($q) {
                $q->where('code', 112)->where('status', true);
            })
            ->where('status', true)
            ->orderBy('code')
            ->get();

        $moneyAccountIds = $moneyAccounts->pluck('id')->toArray();
        try {
            $collectionMoneyAccounts = $collectionService->bankAccounts();
        } catch (ValidationException) {
            $collectionMoneyAccounts = collect();
        }

        try {
            $supplierMoneyAccounts = $supplierPaymentService->bankAccounts();
        } catch (ValidationException) {
            $supplierMoneyAccounts = collect();
        }

        if (!empty($transactionId)) {
            $transaction = Transaction::query()
                ->whereIn('user_id', $this->transactionOwnerIds())
                ->with('entries')
                ->findOrFail($transactionId);

            abort_if(
                $transaction->collection_id !== null,
                409,
                'Transaction thuộc phiếu thu công nợ đã hoàn tất và không thể sửa riêng lẻ.'
            );

            // Kiểm tra xem transaction này có entry nào thuộc tài khoản ngân hàng không
            $hasBankAccount = $transaction->entries->contains(function ($entry) use ($moneyAccountIds) {
                return in_array($entry->account_id, $moneyAccountIds);
            });

            if (!$hasBankAccount) {
                // Không hợp lệ: transaction này không thuộc loại phiếu ngân hàng
                return redirect()->back()->with('error', 'Phiếu này không phải phiếu ngân hàng.');
            }

            // Lấy mainEntry: entry thuộc tài khoản ngân hàng
            $mainEntry = $transaction->entries->firstWhere(function ($entry) use ($moneyAccountIds) {
                return in_array($entry->account_id, $moneyAccountIds);
            });

            // Lấy contraEntry: entry còn lại
            $contraEntry = $transaction->entries->firstWhere(function ($entry) use ($moneyAccountIds) {
                return !in_array($entry->account_id, $moneyAccountIds);
            });
        }

        return view('admin.cash-bank.form', compact(
            'type',
            'moneyAccounts',
            'collectionMoneyAccounts',
            'supplierMoneyAccounts',
            'transaction',
            'mainEntry',
            'contraEntry'
        ));
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

    public function store(Request $request)
    {
        abort(410, 'Phiếu ngân hàng generic đã bị đóng. Vui lòng dùng nghiệp vụ canonical thu công nợ khách hàng hoặc trả công nợ nhà cung cấp.');

        if ($request->input('obj_type') === 'client') {
            abort(410, 'Ghi TK131 khách hàng qua phiếu ngân hàng generic đã bị đóng. Vui lòng dùng luồng thu công nợ khách hàng canonical.');
        }

        if ($request->input('obj_type') === 'supplier') {
            abort(410, 'Thanh toán nhà cung cấp qua luồng ngân hàng legacy đã được vô hiệu hóa cho tới Phase 6C.');
        }

        $credentials = $request->validate([
            'transaction_date'   => 'required|date_format:Y-m-d',
            'obj_type'           => ['required', Rule::in(['client', 'supplier'])],
            'account_id'         => ['required', 'exists:accounts,id'], // tài khoản tiền
            'obj_id'             => [
                'required',
                'integer',
                Rule::when($request->obj_type === 'client', ['exists:clients,id']),
                Rule::when($request->obj_type === 'supplier', ['exists:suppliers,id']),
            ],
            'type'               => ['required', Rule::in(['debit_notice', 'credit_notice'])],
            'amount'             => 'required|numeric|min:0',
            'description'        => 'nullable|string|max:255',
            'document_type'      => 'nullable|string|max:255',
            'attachment'         => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,webp'],
            'reference_number'   => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($credentials, $request) {
            $userId = Auth::id();
            $credentials['created_by'] = $userId;

            // Nếu là phiếu chi, kiểm tra số dư tài khoản tiền
            if ($credentials['type'] === 'debit_notice') {
                $balance = $this->getClosingBalanceByCode($credentials['account_id']);

                if (!$balance['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $balance['message']
                    ], 400);
                }

                $availableAmount = $balance['closing_balance_debit'] - $balance['closing_balance_credit'];

                if ($availableAmount < $credentials['amount']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tài khoản không đủ số dư để chi số tiền này.'
                    ], 400);
                }
            }

            // Xử lý file đính kèm nếu có
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('attachments/cash_transactions', $filename, 'public');
                $credentials['attachment'] = "attachments/cash_transactions/$filename";
            }

            // Tạo phiếu (transaction)
            $transaction = Transaction::create([
                'user_id'            => $userId,
                'transaction_date'   => $credentials['transaction_date'],
                'description'        => $credentials['description'] ?? null,
                'reference_number'   => $credentials['reference_number'] ?? null,
                'type'               => $credentials['type'],
                'document_type'      => $credentials['document_type'] ?? null,
                'attachment'         => $credentials['attachment'] ?? null,
                'created_by'         => $credentials['created_by'],
                'status'             => Transaction::STATUS_COMPLETED,
            ]);

            // Xác định đối tượng
            $tableableType = $credentials['obj_type'] === 'client'
                ? 'App\\Models\\Client'
                : 'App\\Models\\Supplier';
            $tableableId = $credentials['obj_id'];

            // Tự xác định tài khoản đối ứng theo type + obj_type
            $contraCode = match ([$credentials['type'], $credentials['obj_type']]) {
                ['credit_notice', 'supplier']  => '331',
                ['debit_notice', 'supplier'] => '331',
            };

            $contraAccountId = Account::where('code', $contraCode)->value('id');

            if (!$contraAccountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tài khoản đối ứng phù hợp.'
                ], 400);
            }

            $amount = $credentials['amount'];

            // Tạo 2 bản ghi entries
            if ($credentials['type'] === 'credit_notice') {
                // Báo Nợ: tiền tăng Nợ, công nợ giảm Có
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $credentials['account_id'],
                    'debit_amount'     => $amount,
                    'credit_amount'    => 0,
                ]);
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $contraAccountId,
                    'debit_amount'     => 0,
                    'credit_amount'    => $amount,
                    'tableable_type'   => $tableableType,
                    'tableable_id'     => $tableableId,
                ]);
            } else {
                // Báo Có: tiền giảm Có, công nợ tăng Nợ
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $credentials['account_id'],
                    'debit_amount'     => 0,
                    'credit_amount'    => $amount,
                ]);
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $contraAccountId,
                    'debit_amount'     => $amount,
                    'credit_amount'    => 0,
                    'tableable_type'   => $tableableType,
                    'tableable_id'     => $tableableId,
                ]);
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Tạo phiếu thu/chi thành công.',
                'redirect' => '/admin/transactions/bank'
            ]);
        });
    }

    private function getClosingBalanceByCode($accountId)
    {
        if (!$accountId) {
            return [
                'success' => false,
                'message' => 'Vui lòng cung cấp tài khoản.'
            ];
        }

        $query = "
        SELECT
            ma.id,
            ma.code,
            ma.name,
            GREATEST(SUM(COALESCE(te.debit_amount, 0)) - SUM(COALESCE(te.credit_amount, 0)), 0) AS closing_balance_debit,
            GREATEST(SUM(COALESCE(te.credit_amount, 0)) - SUM(COALESCE(te.debit_amount, 0)), 0) AS closing_balance_credit

        FROM accounts ma
        LEFT JOIN transaction_entries te
            ON te.account_id = ma.id
            AND te.tableable_type IS NULL
            AND te.tableable_id IS NULL
        LEFT JOIN transactions t
            ON t.id = te.transaction_id
            AND t.type != 'other'

        WHERE ma.id = ?
        GROUP BY ma.id, ma.code, ma.name
        LIMIT 1
    ";

        $result = DB::selectOne($query, [$accountId]);

        if (!$result) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy tài khoản.'
            ];
        }

        return [
            'success' => true,
            'account_code' => $result->code,
            'account_name' => $result->name,
            'closing_balance_debit' => $result->closing_balance_debit,
            'closing_balance_credit' => $result->closing_balance_credit,
        ];
    }

    private function transactionOwnerIds(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $ownerIds = collect([(int) $user->id]);

        if ((int) $user->role_id === 3 && $user->manager_id) {
            $ownerIds->push((int) $user->manager_id);
        }

        $managedBranchIds = User::query()
            ->where('manager_id', $user->id)
            ->where('role_id', 2)
            ->pluck('id');

        return $ownerIds
            ->merge($managedBranchIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function update(Request $request)
    {
        abort(410, 'Sửa phiếu ngân hàng generic đã bị đóng.');

        $transactionId = $request->input('transaction_id');

        if ($transactionId) {
            $protectedTransaction = Transaction::query()
                ->whereIn('user_id', $this->transactionOwnerIds())
                ->findOrFail($transactionId);
            abort_if(
                $protectedTransaction->collection_id !== null,
                409,
                'Transaction thuộc phiếu thu công nợ đã hoàn tất và không thể sửa riêng lẻ.'
            );
        }

        if ($request->input('obj_type') === 'client') {
            abort(410, 'Sửa TK131 khách hàng qua phiếu ngân hàng generic đã bị đóng. Vui lòng dùng luồng thu công nợ khách hàng canonical.');
        }

        if ($request->input('obj_type') === 'supplier') {
            abort(410, 'Thanh toán nhà cung cấp qua luồng ngân hàng legacy đã được vô hiệu hóa cho tới Phase 6C.');
        }

        $credentials = $request->validate([
            'transaction_id'     => 'required|integer|exists:transactions,id',
            'transaction_date'   => 'required|date_format:Y-m-d',
            'obj_type'           => ['required', Rule::in(['client', 'supplier'])],
            'account_id'         => ['required', 'exists:accounts,id'], // tài khoản tiền
            'obj_id'             => [
                'required',
                'integer',
                Rule::when($request->obj_type === 'client', ['exists:clients,id']),
                Rule::when($request->obj_type === 'supplier', ['exists:suppliers,id']),
            ],
            'type'               => ['required', Rule::in(['debit_notice', 'credit_notice'])],
            'amount'             => 'required|numeric|min:0',
            'description'        => 'nullable|string|max:255',
            'document_type'      => 'nullable|string|max:255',
            'attachment'         => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,webp'],
            'reference_number'   => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($credentials, $request, $transactionId) {
            $transaction = Transaction::query()
                ->whereIn('user_id', $this->transactionOwnerIds())
                ->findOrFail($transactionId);

            // Nếu là phiếu chi, kiểm tra số dư tài khoản tiền
            if ($credentials['type'] === 'debit_notice') {
                $balance = $this->getClosingBalanceByCode($credentials['account_id']);

                if (!$balance['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $balance['message']
                    ], 400);
                }

                $availableAmount = $balance['closing_balance_debit'] - $balance['closing_balance_credit'];

                if ($availableAmount < $credentials['amount']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tài khoản không đủ số dư để chi số tiền này.'
                    ], 400);
                }
            }

            // Xử lý file đính kèm
            if ($request->hasFile('attachment')) {
                if ($transaction->attachment) {
                    deleteImage($transaction->attachment);
                }
                $file = $request->file('attachment');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('attachments/cash_transactions', $filename, 'public');
                $credentials['attachment'] = "attachments/cash_transactions/$filename";
            }
            if ($request->input('remove_attachment') == '1' && $transaction->attachment) {
                deleteImage($transaction->attachment);
                $credentials['attachment'] = null;
            }

            // Update transaction
            $transaction->update([
                'transaction_date'   => $credentials['transaction_date'],
                'description'        => $credentials['description'] ?? null,
                'reference_number'   => $credentials['reference_number'] ?? null,
                'type'               => $credentials['type'],
                'document_type'      => $credentials['document_type'] ?? null,
                'attachment'         => $credentials['attachment'] ?? null,
            ]);

            // Tự xác định tài khoản đối ứng theo type + obj_type
            $contraCode = match ([$credentials['type'], $credentials['obj_type']]) {
                ['credit_notice', 'supplier']  => '331',
                ['debit_notice', 'supplier'] => '331',
            };

            $contraAccountId = Account::where('code', $contraCode)->value('id');

            if (!$contraAccountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tài khoản đối ứng phù hợp.'
                ], 400);
            }

            $tableableType = $credentials['obj_type'] === 'client'
                ? 'App\\Models\\Client'
                : 'App\\Models\\Supplier';
            $tableableId = $credentials['obj_id'];

            $amount = $credentials['amount'];

            // Xóa entries cũ
            $transaction->entries()->delete();

            // Tạo lại entries mới
            if ($credentials['type'] === 'credit_notice') {
                // Báo Nợ: tiền tăng Nợ, công nợ giảm Có
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $credentials['account_id'],
                    'debit_amount'     => $amount,
                    'credit_amount'    => 0,
                ]);
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $contraAccountId,
                    'debit_amount'     => 0,
                    'credit_amount'    => $amount,
                    'tableable_type'   => $tableableType,
                    'tableable_id'     => $tableableId,
                ]);
            } else {
                // Báo Có: tiền giảm Có, công nợ tăng Nợ
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $credentials['account_id'],
                    'debit_amount'     => 0,
                    'credit_amount'    => $amount,
                ]);
                TransactionEntry::create([
                    'transaction_id'   => $transaction->id,
                    'account_id'       => $contraAccountId,
                    'debit_amount'     => $amount,
                    'credit_amount'    => 0,
                    'tableable_type'   => $tableableType,
                    'tableable_id'     => $tableableId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phiếu thu/chi thành công.',
                'redirect' => '/admin/bank-transactions'
            ]);
        });
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:transactions,id',
        ]);

        return DB::transaction(function () use ($request) {
            $transactionIds = $request->input('ids');

            foreach ($transactionIds as $transactionId) {
                $transaction = Transaction::query()
                    ->whereIn('user_id', $this->transactionOwnerIds())
                    ->find($transactionId);
                if ($transaction) {
                    abort_if(
                        $transaction->collection_id !== null,
                        409,
                        'Transaction thuộc phiếu thu công nợ đã hoàn tất và không thể xóa riêng lẻ.'
                    );
                    // Xóa file nếu có
                    if ($transaction->attachment) {
                        deleteImage($transaction->attachment);
                    }
                    // Xóa transaction
                    $transaction->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa phiếu thành công.'
            ]);
        });
    }
}
