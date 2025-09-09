<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DebtController extends Controller
{

    public function customer(Request $request)
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

        $clientsQuery = DB::table('clients as c')
            ->select('c.id', 'c.name', 'c.code', 'c.phone');

        if ($nameFilter) {
            $clientsQuery->where('c.name', 'like', "%$nameFilter%");
        }

        $debtReports = $clientsQuery->get()
            ->map(function ($client) use ($startDate, $endDate) {
                $so_du_no_dau = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Client')
                    ->where('te.tableable_id', $client->id)
                    ->where('t.transaction_date', '<', $startDate)
                    ->sum('te.debit_amount');

                $so_du_co_dau = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Client')
                    ->where('te.tableable_id', $client->id)
                    ->where('t.transaction_date', '<', $startDate)
                    ->sum('te.credit_amount');

                $ghi_no = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Client')
                    ->where('te.tableable_id', $client->id)
                    ->whereBetween('t.transaction_date', [$startDate, $endDate])
                    ->sum('te.debit_amount');

                $ghi_co = DB::table('transaction_entries as te')
                    ->join('transactions as t', 't.id', '=', 'te.transaction_id')
                    ->where('te.tableable_type', 'App\\Models\\Client')
                    ->where('te.tableable_id', $client->id)
                    ->whereBetween('t.transaction_date', [$startDate, $endDate])
                    ->sum('te.credit_amount');

                $so_du_rong = ($so_du_no_dau + $ghi_no) - ($so_du_co_dau + $ghi_co);

                return (object)[
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'client_phone' => $client->phone,
                    'opening_debit' => $so_du_no_dau,
                    'opening_credit' => $so_du_co_dau,
                    'period_debit' => $ghi_no,
                    'period_credit' => $ghi_co,
                    'ending_debit' => $so_du_rong > 0 ? $so_du_rong : 0,
                    'ending_credit' => $so_du_rong < 0 ? abs($so_du_rong) : 0,
                ];
            })
            ->filter(
                fn($i) =>
                $i->opening_debit || $i->opening_credit || $i->period_debit || $i->period_credit
            )
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
            ->select('s.id', 's.name', 's.code', 's.phone');

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
                    'supplier_code' => $supplier->code,
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
