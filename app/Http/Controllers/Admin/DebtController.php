<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\Accounting\CustomerDebtSnapshotService;
use App\Services\SupplierDebtReportService;
use App\Support\DecimalAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DebtController extends Controller
{
    public function customer(Request $request, CustomerDebtSnapshotService $snapshotService)
    {
        [$startDate, $endDate] = $this->customerReportDates($request);
        $nameFilter = trim((string) $request->input('name', ''));

        try {
            $ownerId = $request->user()->ownerId();
            $debtReports = $snapshotService->report(
                $ownerId,
                $startDate,
                $endDate,
                $nameFilter
            );
        } catch (\RuntimeException $exception) {
            Log::error('Không thể lập báo cáo công nợ khách hàng vì thiếu tài khoản 131 đang hoạt động.');

            abort(500, $exception->getMessage());
        }

        $totals = $this->debtReportTotals($debtReports);

        if ($request->ajax()) {
            return response()->json($debtReports);
        }

        return view('admin.debt.customer', [
            'clientDebts' => $debtReports,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totals' => $totals,
        ]);
    }

    private function customerReportDates(Request $request): array
    {
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        if ($fromDate !== '' || $toDate !== '') {
            if ($fromDate === '' || $toDate === '') {
                abort(422, 'Customer debt report requires both from_date and to_date.');
            }

            try {
                $startDate = Carbon::createFromFormat('!Y-m-d', $fromDate)->toDateString();
                $endDate = Carbon::createFromFormat('!Y-m-d', $toDate)->toDateString();
            } catch (\Throwable $exception) {
                abort(422, 'Invalid customer debt report date range.');
            }

            if ($startDate !== $fromDate || $endDate !== $toDate) {
                abort(422, 'Invalid customer debt report date range.');
            }

            if ($startDate > $endDate) {
                abort(422, 'Customer debt report start date must not be after end date.');
            }

            return [$startDate, $endDate];
        }

        $dateRange = trim((string) $request->input('date_range', ''));

        if ($dateRange === '') {
            $endDate = Carbon::now()->toDateString();

            return [Carbon::now()->subMonth()->toDateString(), $endDate];
        }

        $parts = preg_split('/\s+-\s+/', $dateRange);

        if (count($parts) !== 2) {
            abort(422, 'Invalid customer debt report date range.');
        }

        try {
            $startDate = Carbon::createFromFormat('d/m/Y', trim($parts[0]))->toDateString();
            $endDate = Carbon::createFromFormat('d/m/Y', trim($parts[1]))->toDateString();
        } catch (\Throwable $exception) {
            abort(422, 'Invalid customer debt report date range.');
        }

        if ($startDate > $endDate) {
            abort(422, 'Customer debt report start date must not be after end date.');
        }

        return [$startDate, $endDate];
    }

    public function supplier(Request $request, SupplierDebtReportService $reportService)
    {
        [$startDate, $endDate] = $this->supplierReportDates($request);
        $supplierDebts = $reportService->report(
            $request->user(),
            $startDate,
            $endDate,
            (string) $request->input('name', '')
        );
        $totals = $this->debtReportTotals($supplierDebts);

        if ($request->ajax()) {
            return response()->json($supplierDebts);
        }

        return view('admin.debt.supplier', compact('supplierDebts', 'startDate', 'endDate', 'totals'));
    }

    private function supplierReportDates(Request $request): array
    {
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        if ($fromDate !== '' || $toDate !== '') {
            if ($fromDate === '' || $toDate === '') {
                abort(422, 'Supplier debt report requires both from_date and to_date.');
            }

            try {
                $startDate = Carbon::createFromFormat('!Y-m-d', $fromDate)->toDateString();
                $endDate = Carbon::createFromFormat('!Y-m-d', $toDate)->toDateString();
            } catch (\Throwable $exception) {
                abort(422, 'Invalid supplier debt report date range.');
            }

            if ($startDate !== $fromDate || $endDate !== $toDate) {
                abort(422, 'Invalid supplier debt report date range.');
            }

            if ($startDate > $endDate) {
                abort(422, 'Supplier debt report start date must not be after end date.');
            }

            return [$startDate, $endDate];
        }

        $dateRange = trim((string) $request->input('date_range', ''));

        if ($dateRange === '') {
            $endDate = Carbon::now()->toDateString();

            return [Carbon::now()->subMonth()->toDateString(), $endDate];
        }

        $parts = preg_split('/\s+-\s+/', $dateRange);

        if (count($parts) !== 2) {
            abort(422, 'Invalid supplier debt report date range.');
        }

        try {
            $startDate = Carbon::createFromFormat('d/m/Y', trim($parts[0]))->toDateString();
            $endDate = Carbon::createFromFormat('d/m/Y', trim($parts[1]))->toDateString();
        } catch (\Throwable $exception) {
            abort(422, 'Invalid supplier debt report date range.');
        }

        if ($startDate > $endDate) {
            abort(422, 'Supplier debt report start date must not be after end date.');
        }

        return [$startDate, $endDate];
    }

    private function debtReportTotals(Collection $rows): array
    {
        $fields = [
            'opening_debit',
            'opening_credit',
            'period_debit',
            'period_credit',
            'ending_debit',
            'ending_credit',
        ];
        $totals = array_fill_keys($fields, '0.00');

        foreach ($rows as $row) {
            foreach ($fields as $field) {
                $totals[$field] = DecimalAmount::add(
                    $totals[$field],
                    (string) ($row->{$field} ?? '0.00')
                );
            }
        }

        return $totals;
    }

    public function create()
    {
        return view('admin.debt.beginning');
    }

    public function store(Request $request)
    {
        if ($request->input('object_type') === 'client') {
            abort(410, 'Tạo công nợ đầu kỳ khách hàng đã tạm đóng cho tới khi có kiến trúc opening debt cân bằng.');
        }

        $credentials = Validator::make($request->all(), [
            'transaction_date' => 'required|date_format:Y-m-d',
            'object_type' => 'required|in:client,supplier',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|max:255',
            'object_id' => [
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

        if ($credentials['object_type'] === 'supplier') {
            abort(410, 'Tạo công nợ đầu kỳ nhà cung cấp qua luồng legacy đã được vô hiệu hóa.');
        }

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

            $message = 'Tạo công nợ đầu kỳ thành công.';

            $redirect = $credentials['object_type'] === 'client'
                ? '/admin/debts/customer'
                : '/admin/debts/supplier';

            return response()->json([
                'message' => $message,
                'data' => $redirect,
            ]);
        });
    }
}
