<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class JournalEntryController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
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

            $transactions = DB::table('transactions as t')
                ->where('t.user_id', Auth::id())
                ->join('transaction_entries as te', 't.id', '=', 'te.transaction_id')
                ->join('accounts as acc', 'te.account_id', '=', 'acc.id')
                ->leftJoin('clients as c', function ($join) {
                    $join->on('te.tableable_id', '=', 'c.id')
                        ->where('te.tableable_type', '=', 'App\\Models\\Client');
                })
                ->leftJoin('suppliers as s', function ($join) {
                    $join->on('te.tableable_id', '=', 's.id')
                        ->where('te.tableable_type', '=', 'App\\Models\\Supplier');
                })
                ->whereNotNull('te.id')
                ->whereBetween('t.transaction_date', [$startDate, $endDate])
                ->when($nameFilter, function ($query, $nameFilter) {
                    $query->where(function ($q) use ($nameFilter) {
                        $q->where('c.name', 'like', "%$nameFilter%")
                            ->orWhere('s.name', 'like', "%$nameFilter%")
                            ->orWhere('c.phone', 'like', "%$nameFilter%")
                            ->orWhere('s.phone', 'like', "%$nameFilter%");
                    });
                })
                ->select([
                    't.id as transaction_id',
                    't.transaction_date',
                    't.type as transaction_type',
                    't.document_type',
                    't.attachment',
                    DB::raw("MAX(CASE WHEN te.debit_amount > 0 THEN CONCAT(acc.code) END) as debit_account"),
                    DB::raw("MAX(CASE WHEN te.credit_amount > 0 THEN CONCAT(acc.code) END) as credit_account"),
                    DB::raw("MAX(te.debit_amount + te.credit_amount) as amount"),
                    DB::raw("MAX(CASE WHEN te.debit_amount > 0 THEN te.note ELSE '' END) as note"),
                    DB::raw("COALESCE(MAX(c.name), MAX(s.name)) as object_name"),
                    DB::raw("COALESCE(MAX(c.phone), MAX(s.phone)) as object_phone"),
                ])
                ->groupBy('t.id', 't.transaction_date', 't.type', 't.document_type', 't.attachment')
                ->orderByDesc('t.transaction_date')
                ->get();

            return response()->json([
                'success' => true,
                'html' => view('admin.journal-entries._table', compact('transactions'))->render()
            ]);
        }

        return view('admin.journal-entries.index');
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
                'message' => 'Xóa phiếu thành công.'
            ]);
        });
    }
}
