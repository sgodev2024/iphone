<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class JournalEntryController extends Controller
{
    public function __construct(private readonly BranchContext $branchContext) {}

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

            $transactionsQuery = DB::table('transactions as t');
            if (! Schema::hasColumn('transactions', 'branch_id')
                || ! $this->branchContext->isGlobal($request->user())
            ) {
                $transactionsQuery->whereIn('t.user_id', $this->transactionOwnerIds($request));
            }
            $this->branchContext->scope($transactionsQuery, $request->user(), 't.branch_id');
            $transactions = $transactionsQuery
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
                $query = Transaction::query()->whereKey($transactionId);
                if (! Schema::hasColumn('transactions', 'branch_id')
                    || ! $this->branchContext->isGlobal($request->user())
                ) {
                    $query->whereIn('user_id', $this->transactionOwnerIds($request));
                }
                $this->branchContext->scope($query, $request->user());
                $transaction = $query->first();
                if (! $transaction) {
                    continue;
                }
                // Xóa file nếu có
                if ($transaction->attachment) {
                    deleteImage($transaction->attachment);
                }
                // Xóa transaction
                $transaction->delete();
            }

            return response()->json([
                'message' => 'Xóa phiếu thành công.'
            ]);
        });
    }

    private function transactionOwnerIds(Request $request): array
    {
        $user = $request->user();

        return collect([$user->ownerId(), $user->id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
