<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DebtController extends Controller
{

    public function customer(Request $request)
    {
        $dateRange = $request->input('date_range');
        $nameFilter = trim((string) $request->input('name', ''));

        if ($dateRange) {
            [$start, $end] = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->toDateString();
            $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->toDateString();
        } else {
            $endDate = Carbon::now()->toDateString();
            $startDate = Carbon::now()->subMonth()->toDateString();
        }

        $receivableAccountId = Account::query()
            ->where('code', '131')
            ->where('status', true)
            ->value('id');

        if (! $receivableAccountId) {
            Log::error('Không thể lập báo cáo công nợ khách hàng vì thiếu tài khoản 131 đang hoạt động.');

            abort(500, 'Không tìm thấy tài khoản phải thu khách hàng (131) đang hoạt động.');
        }

        $ownerId = $request->user()->ownerId();
        $debtReports = DB::table('clients as c')
            ->join('transaction_entries as te', function ($join) use ($receivableAccountId) {
                $join->on('te.tableable_id', '=', 'c.id')
                    ->where('te.tableable_type', Client::class)
                    ->where('te.account_id', $receivableAccountId);
            })
            ->join('transactions as t', 't.id', '=', 'te.transaction_id')
            ->where('c.user_id', $ownerId)
            ->where('t.transaction_date', '<=', $endDate)
            ->when($nameFilter !== '', function ($query) use ($nameFilter) {
                $query->where('c.name', 'like', "%{$nameFilter}%");
            })
            ->select('c.id', 'c.code', 'c.name', 'c.phone')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date < ? THEN te.debit_amount - te.credit_amount ELSE 0 END), 0) AS opening_balance',
                [$startDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date >= ? AND t.transaction_date <= ? THEN te.debit_amount ELSE 0 END), 0) AS period_debit',
                [$startDate, $endDate]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN t.transaction_date >= ? AND t.transaction_date <= ? THEN te.credit_amount ELSE 0 END), 0) AS period_credit',
                [$startDate, $endDate]
            )
            ->groupBy('c.id', 'c.code', 'c.name', 'c.phone')
            ->orderBy('c.name')
            ->orderBy('c.id')
            ->get()
            ->map(function ($client) {
                $openingBalance = (float) $client->opening_balance;
                $periodDebit = (float) $client->period_debit;
                $periodCredit = (float) $client->period_credit;
                $endingBalance = $openingBalance + $periodDebit - $periodCredit;

                return (object) [
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'client_phone' => $client->phone,
                    'opening_debit' => max($openingBalance, 0),
                    'opening_credit' => max(-$openingBalance, 0),
                    'period_debit' => $periodDebit,
                    'period_credit' => $periodCredit,
                    'ending_debit' => max($endingBalance, 0),
                    'ending_credit' => max(-$endingBalance, 0),
                ];
            })
            ->filter(fn ($item) => $item->opening_debit
                || $item->opening_credit
                || $item->period_debit
                || $item->period_credit)
            ->values();

        if ($request->ajax()) {
            return response()->json($debtReports);
        }

        return view('admin.debt.customer', [
            'clientDebts' => $debtReports,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }


    public function supplier(Request $request)
    {
        $dateRange = $request->input('date_range');
        $nameFilter = $request->input('name');

        if ($dateRange) {
            [$start, $end] = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
        } else {
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subMonth()->startOfDay();
        }

        $suppliersQuery = DB::table('suppliers as s')
            ->select('s.id', 's.name', 's.phone');

        if ($nameFilter) {
            $suppliersQuery->where('s.name', 'like', "%$nameFilter%");
        }

        $supplierDebts = $suppliersQuery->get()
            ->map(function ($supplier) use ($startDate, $endDate) {
                $openingDebit = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Supplier')
                    ->where('te.tableable_id', $supplier->id)
                    ->where('t.transaction_date', '<', $startDate)
                    ->sum('te.debit_amount');

                $openingCredit = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Supplier')
                    ->where('te.tableable_id', $supplier->id)
                    ->where('t.transaction_date', '<', $startDate)
                    ->sum('te.credit_amount');

                $periodDebit = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Supplier')
                    ->where('te.tableable_id', $supplier->id)
                    ->whereBetween('t.transaction_date', [$startDate, $endDate])
                    ->sum('te.debit_amount');

                $periodCredit = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Supplier')
                    ->where('te.tableable_id', $supplier->id)
                    ->whereBetween('t.transaction_date', [$startDate, $endDate])
                    ->sum('te.credit_amount');

                $endingBalance = ($openingDebit + $periodDebit) - ($openingCredit + $periodCredit);

                return (object)[
                    'supplier_code' => 'NCC' . str_pad($supplier->id, 5, '0', STR_PAD_LEFT),
                    'supplier_name' => $supplier->name,
                    'supplier_phone' => $supplier->phone,
                    'opening_debit' => $openingDebit,
                    'opening_credit' => $openingCredit,
                    'period_debit' => $periodDebit,
                    'period_credit' => $periodCredit,
                    'ending_debit' => $endingBalance > 0 ? $endingBalance : 0,
                    'ending_credit' => $endingBalance < 0 ? abs($endingBalance) : 0,
                ];
            })
            ->filter(
                fn($item) =>
                $item->opening_debit || $item->opening_credit || $item->period_debit || $item->period_credit
            )
            ->values();

        if ($request->ajax()) {
            return response()->json($supplierDebts);
        }

        return view('admin.debt.supplier', compact('supplierDebts', 'startDate', 'endDate'));
    }

    public function create()
    {
        return view('admin.debt.beginning');
    }

    public function store(Request $request)
    {
        $credentials = Validator::make($request->all(), [
            'transaction_date' => 'required|date_format:Y-m-d',
            'object_type'      => 'required|in:client,supplier',
            'type'             => 'required|in:income,expense',
            'amount'           => 'required|numeric|min:0',
            'description'      => 'nullable|max:255',
            'object_id'        => [
                'required',
                'integer',
                Rule::when($request->object_type === 'client', ['exists:clients,id']),
                Rule::when($request->object_type === 'supplier', ['exists:suppliers,id']),
            ],
        ], [
            // Custom error messages
            'transaction_date.required' => 'Vui lòng chọn ngày giao dịch.',
            'transaction_date.date_format' => 'Ngày giao dịch không đúng định dạng (Y-m-d).',
            'object_type.required' => 'Vui lòng chọn loại đối tượng.',
            'object_type.in' => 'Loại đối tượng không hợp lệ (chỉ client hoặc supplier).',
            'type.required' => 'Vui lòng chọn loại giao dịch.',
            'type.in' => 'Loại giao dịch không hợp lệ (chỉ income hoặc expense).',
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.numeric' => 'Số tiền phải là số.',
            'amount.min' => 'Số tiền phải lớn hơn hoặc bằng 0.',
            'description.max' => 'Mô tả không được vượt quá 255 ký tự.',
            'object_id.required' => 'Vui lòng chọn đối tượng.',
            'object_id.integer' => 'ID đối tượng không hợp lệ.',
            'object_id.exists' => 'Đối tượng không tồn tại trong hệ thống.',
        ], [
            // Custom attribute names
            'transaction_date' => 'ngày giao dịch',
            'object_type' => 'loại đối tượng',
            'type' => 'loại giao dịch',
            'amount' => 'số tiền',
            'description' => 'mô tả',
            'object_id' => 'đối tượng',
        ]);

        if ($credentials->fails()) {
            return response()->json([
                'message' => $credentials->errors()->first(),
            ], 422);
        }

        $credentials = $credentials->validate();

        return DB::transaction(function () use ($credentials) {
            $transaction = Transaction::create([
                'transaction_date' => $credentials['transaction_date'],
                'description' => $credentials['description'],
                'type' => 'other', // phiếu công nợ đầu kỳ
                'created_by' => Auth::id(),
                'user_id' => Auth::id(),
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            // Xác định đối tượng (customer hoặc supplier)
            $tableableType = $credentials['object_type'] === 'client'
                ? 'App\\Models\\Client'
                : 'App\\Models\\Supplier';
            $tableableId = $credentials['object_id'];

            // Xác định tài khoản kế toán theo loại phiếu
            if ($credentials['type'] === 'income') {
                // Phiếu thu → công nợ phải thu KH
                $accountId = Account::where('code', 131)->value('id');
                $debitAmount = $credentials['amount'];
                $creditAmount = 0;
            } else {
                // Phiếu chi → công nợ phải trả NCC
                $accountId = Account::where('code', 331)->value('id');
                $debitAmount = 0;
                $creditAmount = $credentials['amount'];
            }

            $transaction->entries()->create([
                'account_id' => $accountId,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'tableable_type' => $tableableType,
                'tableable_id' => $tableableId,
                'note' => 'Công nợ đầu kỳ',
            ]);

            $message = "Tạo công nợ đầu kỳ thành công.";

            $redirect = $credentials['object_type'] === 'client'
                ? '/admin/debts/customer'
                : '/admin/debts/supplier';

            return response()->json([
                'message' => $message,
                'data' => $redirect
            ]);
        });
    }
}
