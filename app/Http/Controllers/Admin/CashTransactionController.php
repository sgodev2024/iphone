<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SampleCashTransactionExport;
use App\Http\Controllers\Controller;
use App\Imports\CashTransactionImport;
use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CashTransactionController extends Controller
{
    public function index()
    {
        return view('admin.cash-bank.cash');
    }

    public function save(Request $request)
    {
        $type = 'cash'; // Phiếu tiền mặt
        $transaction = null;
        $mainEntry = null;
        $contraEntry = null;

        $transactionId = $request->input('transactionId');

        // Lấy danh sách tài khoản tiền mặt (con của 111)
        $moneyAccounts = Account::query()
            ->whereHas('parent', function ($q) {
                $q->where('code', 111);
            })
            ->where('is_default', false)
            ->where('status', true)
            ->orderBy('code')
            ->get();

        $moneyAccountIds = $moneyAccounts->pluck('id')->toArray();

        if (!empty($transactionId)) {
            // Lấy transaction + entries
            $transaction = Transaction::with('entries')->findOrFail($transactionId);

            // Kiểm tra transaction này có entry nào thuộc tài khoản tiền mặt không
            $hasCashAccount = $transaction->entries->contains(function ($entry) use ($moneyAccountIds) {
                return in_array($entry->account_id, $moneyAccountIds);
            });

            if (!$hasCashAccount) {
                // Không hợp lệ: transaction này không phải phiếu tiền mặt
                return redirect()->back()->with('error', 'Phiếu này không phải phiếu tiền mặt.');
            }

            // Lấy mainEntry: entry thuộc tài khoản tiền mặt
            $mainEntry = $transaction->entries->firstWhere(function ($entry) use ($moneyAccountIds) {
                return in_array($entry->account_id, $moneyAccountIds);
            });

            // Lấy contraEntry: entry đối ứng (entry còn lại)
            $contraEntry = $transaction->entries->firstWhere(function ($entry) use ($moneyAccountIds) {
                return !in_array($entry->account_id, $moneyAccountIds);
            });

            // dd($mainEntry, $contraEntry);
        }

        return view('admin.cash-bank.form', compact(
            'type',
            'moneyAccounts',
            'transaction',
            'mainEntry',
            'contraEntry'
        ));
    }

    public function store(Request $request)
    {
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
            'type'               => ['required', Rule::in(['income', 'expense'])],
            'amount'             => 'required|numeric|min:0',
            'description'        => 'nullable|string|max:255',
            'document_type'      => 'nullable|string|max:255',
            'attachment'         => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,webp'],
            'reference_number'   => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($credentials, $request) {
            $credentials['created_by'] = Auth::id();

            // Nếu là phiếu chi, kiểm tra số dư tài khoản tiền
            if ($credentials['type'] === 'expense') {
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

            // Tạo phiếu giao dịch
            $transaction = Transaction::create([
                'user_id'            => Auth::id(),
                'transaction_date'   => $credentials['transaction_date'],
                'description'        => $credentials['description'] ?? null,
                'reference_number'   => $credentials['reference_number'] ?? null,
                'type'               => $credentials['type'], // income | expense
                'document_type'      => $credentials['document_type'] ?? null,
                'attachment'         => $credentials['attachment'] ?? null,
                'created_by'         => $credentials['created_by'],
            ]);

            // Xác định đối tượng liên quan
            $tableableType = $credentials['obj_type'] === 'client'
                ? 'App\\Models\\Client'
                : 'App\\Models\\Supplier';
            $tableableId = $credentials['obj_id'];

            // Tự xác định tài khoản đối ứng theo type + obj_type
            $contraCode = match ([$credentials['type'], $credentials['obj_type']]) {
                ['income', 'client']  => '131',
                ['income', 'supplier']  => '331',
                ['expense', 'client'] => '131',
                ['expense', 'supplier'] => '331',
            };

            $contraAccountId = Account::where('code', $contraCode)->value('id');

            if (!$contraAccountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tài khoản đối ứng phù hợp.'
                ], 400);
            }

            $amount = $credentials['amount'];

            // Ghi nhận bút toán
            if ($credentials['type'] === 'income') {
                // Thu → tiền tăng (Nợ), đối ứng giảm (Có)
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
                // Chi → tiền giảm (Có), đối ứng tăng (Nợ)
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
                'redirect' => '/admin/transactions/cash'
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

    public function update(Request $request)
    {
        $transactionId = $request->input('transaction_id');

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
            'type'               => ['required', Rule::in(['income', 'expense'])],
            'amount'             => 'required|numeric|min:0',
            'description'        => 'nullable|string|max:255',
            'document_type'      => 'nullable|string|max:255',
            'attachment'         => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,webp'],
            'reference_number'   => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($credentials, $request, $transactionId) {
            $transaction = Transaction::findOrFail($transactionId);

            // Nếu là phiếu chi, kiểm tra số dư tài khoản tiền
            if ($credentials['type'] === 'expense') {
                $balance = $this->getClosingBalanceByCode($credentials['account_id']);

                if (!$balance['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $balance['message']
                    ], 400);
                }

                $availableAmount = $balance['closing_balance_debit'] - $balance['closing_balance_credit'];
                $oldAmount = $transaction->entries->first()->debit_amount > 0 ? $transaction->entries->first()->debit_amount : $transaction->entries->first()->credit_amount;

                if (($availableAmount + $oldAmount) < $credentials['amount']) {
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

            // Cập nhật transaction
            $transaction->update([
                'transaction_date'   => $credentials['transaction_date'],
                'description'        => $credentials['description'] ?? null,
                'reference_number'   => $credentials['reference_number'] ?? null,
                'type'               => $credentials['type'],
                'document_type'      => $credentials['document_type'] ?? null,
                'attachment'         => $credentials['attachment'] ?? null,
            ]);

            // Tự xác định tài khoản đối ứng dựa vào type + obj_type
            $contraCode = match ([$credentials['type'], $credentials['obj_type']]) {
                ['income', 'client']  => '131',
                ['income', 'supplier']  => '331',
                ['expense', 'client'] => '131',
                ['expense', 'supplier'] => '331',
            };

            $contraAccountId = Account::where('code', $contraCode)->value('id');

            if (!$contraAccountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tài khoản đối ứng phù hợp.'
                ], 400);
            }

            // Xác định đối tượng
            $tableableType = $credentials['obj_type'] === 'client'
                ? 'App\\Models\\Client'
                : 'App\\Models\\Supplier';
            $tableableId = $credentials['obj_id'];
            $amount = $credentials['amount'];

            // Xóa entries cũ
            $transaction->entries()->delete();

            // Tạo lại entries đúng chiều
            if ($credentials['type'] === 'income') {
                // Thu: tiền tăng (Nợ), công nợ giảm (Có)
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
                // Chi: tiền giảm (Có), công nợ tăng (Nợ)
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
                'redirect' => '/admin/transactions/cash'
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
                $transaction = Transaction::find($transactionId);
                if ($transaction) {
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

    public function search(Request $request)
    {
        $type = $request->input('type');
        $keyword = $request->input('keyword');

        if (!$type || strlen($keyword) < 3) {
            return response()->json([]);
        }

        $query = match ($type) {
            'client' => Client::query()->where('name', 'like', "%$keyword%"),
            'supplier' => Supplier::query()->where('name', 'like', "%$keyword%"),
            default => null,
        };

        if (!$query) {
            return response()->json([]);
        }

        $results = $query->limit(10)->get()->map(
            fn($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => match ($type) {
                    'client', 'supplier' => $item->name ?? '',
                    default => '',
                },
                'phone' => $item->phone ?? '',
            ]
        );

        return response()->json($results);
    }


    // public function printMultiple(Request $request)
    // {
    //     $ids = $request->input('ids', []);

    //     $transactions = CashTransaction::with(['voucherType', 'cashAccount', 'creator'])
    //         ->whereIn('id', $ids)
    //         ->get();

    //     return view('admin.cash-transaction.print', compact('transactions'));
    // }

    public function list(Request $request)
    {
        $dateRange = $request->query('date_range');

        if ($dateRange) {
            [$from, $to] = explode(' - ', $dateRange);
            $from = Carbon::createFromFormat('d/m/Y', $from)->toDateString();
            $to = Carbon::createFromFormat('d/m/Y', $to)->toDateString();
        } else {
            $from = now()->subMonth()->toDateString();
            $to = now()->toDateString();
        }

        // Lấy danh sách account_id của 111 và các con
        $cashAccountIds = DB::table('accounts')
            ->where(function ($q) {
                $q->where('code', '111')
                    ->orWhere('parent_id', function ($sub) {
                        $sub->select('id')->from('accounts')->where('code', '111')->limit(1);
                    });
            })
            ->pluck('id');

        $entries = DB::table('transactions as t')
            ->where('t.user_id', Auth::id())
            ->join('transaction_entries as te', 'te.transaction_id', '=', 't.id')
            ->join('accounts as ma', 'ma.id', '=', 'te.account_id')

            // Join lấy dòng đối ứng
            ->join('transaction_entries as te_contra', function ($q) {
                $q->on('te_contra.transaction_id', '=', 't.id')
                    ->whereColumn('te_contra.id', '!=', 'te.id');
            })
            ->join('accounts as contra_acc', 'contra_acc.id', '=', 'te_contra.account_id')

            // Lấy thông tin KH/NCC từ dòng đối ứng
            ->leftJoin('clients as c', function ($q) {
                $q->on('c.id', '=', 'te_contra.tableable_id')
                    ->where('te_contra.tableable_type', 'App\\Models\\Client');
            })
            ->leftJoin('suppliers as s', function ($q) {
                $q->on('s.id', '=', 'te_contra.tableable_id')
                    ->where('te_contra.tableable_type', 'App\\Models\\Supplier');
            })
            ->join('users as u', 'u.id', '=', 't.created_by')

            ->where('t.type', '!=', 'other')
            ->whereIn('te.account_id', $cashAccountIds)
            ->whereBetween('t.transaction_date', [$from, $to])

            ->groupBy(
                't.id',
                't.transaction_date',
                't.reference_number',
                't.description',
                't.document_type',
                't.attachment',
                'u.name',
            )
            ->select(
                't.id',
                't.transaction_date',
                't.reference_number',
                't.description',
                't.document_type',
                't.attachment',
                'u.name as creator_name',
                DB::raw("MAX(te.id) as entry_id"),
                DB::raw("COALESCE(MAX(c.name), MAX(s.name)) as related_party"),
                DB::raw("COALESCE(MAX(c.phone), MAX(s.phone)) as related_party_phone"),
                DB::raw("MAX(ma.code) as account_code"),
                DB::raw("MAX(ma.name) as account_name"),
                DB::raw("MAX(contra_acc.code) as contra_code"),
                DB::raw("MAX(contra_acc.name) as contra_name"),
                DB::raw("SUM(te.debit_amount) as debit_amount"),
                DB::raw("SUM(te.credit_amount) as credit_amount")
            )
            ->orderByDesc('t.id')
            ->get();

        $type = 'cash';

        return response()->json([
            'success' => true,
            'html' => view('admin.cash-bank._table', compact('entries', 'type'))->render()
        ]);
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
}
