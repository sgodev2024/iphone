<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierPaymentRequest;
use App\Models\Company;
use App\Services\SupplierPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function companies(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('expense.create'), 403);

        $keyword = trim((string) $request->query('keyword'));

        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Company::query()
                ->where('user_id', $request->user()->ownerId())
                ->where(function ($query) use ($keyword): void {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'phone'])
                ->map(fn (Company $company): array => [
                    'id' => (int) $company->id,
                    'name' => $company->name,
                    'phone' => $company->phone,
                ])
                ->values()
        );
    }

    public function imports(
        Request $request,
        int $companyId,
        SupplierPaymentService $service
    ): JsonResponse {
        abort_unless($request->user()?->hasPermission('expense.create'), 403);

        return response()->json($service->outstandingImports($request->user(), $companyId));
    }

    public function store(
        StoreSupplierPaymentRequest $request,
        int $id,
        SupplierPaymentService $service
    ): JsonResponse|RedirectResponse {
        $result = $service->pay($request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result['replayed']
                    ? 'Yêu cầu đã được xử lý trước đó.'
                    : 'Thanh toán nhà cung cấp thành công.',
                'replayed' => $result['replayed'],
                'transaction_id' => (int) $result['transaction']->id,
                'import_coupon' => [
                    'id' => (int) $result['import_coupon']->id,
                    'paid_amount' => (int) $result['import_coupon']->paid_amount,
                    'debt_amount' => (int) $result['import_coupon']->debt_amount,
                    'payment_status' => $result['import_coupon']->payment_status,
                ],
            ]);
        }

        return redirect()
            ->route('admin.importproduct.importCoupon.detail', ['id' => $id])
            ->with(
                'success',
                $result['replayed']
                    ? 'Yêu cầu thanh toán này đã được xử lý trước đó.'
                    : 'Thanh toán nhà cung cấp thành công.'
            );
    }

    public function storeFromCash(
        StoreSupplierPaymentRequest $request,
        SupplierPaymentService $service
    ): JsonResponse {
        return $this->storeFromUnifiedInstrument($request, $service);
    }

    public function storeFromBank(
        StoreSupplierPaymentRequest $request,
        SupplierPaymentService $service
    ): JsonResponse {
        return $this->storeFromUnifiedInstrument($request, $service);
    }

    private function storeFromUnifiedInstrument(
        StoreSupplierPaymentRequest $request,
        SupplierPaymentService $service
    ): JsonResponse {
        $result = $service->pay($request->user(), $request->validated());
        $transaction = $result['transaction'];
        $moneyEntry = $transaction->entries
            ->first(fn ($entry): bool => (string) $entry->credit_amount !== '0.00');

        return response()->json([
            'message' => $result['replayed']
                ? 'Yêu cầu đã được xử lý trước đó.'
                : 'Trả công nợ nhà cung cấp thành công.',
            'replayed' => $result['replayed'],
            'transaction_id' => (int) $transaction->id,
            'amount' => (int) $request->validated()['amount'],
            'transaction' => [
                'id' => (int) $transaction->id,
                'status' => $transaction->status,
                'reference_number' => $transaction->reference_number,
            ],
            'money_account' => [
                'id' => (int) ($moneyEntry?->account?->id ?? 0),
                'code' => $moneyEntry?->account?->code,
                'name' => $moneyEntry?->account?->name,
            ],
            'import_coupon' => [
                'id' => (int) $result['import_coupon']->id,
                'coupon_code' => $result['import_coupon']->coupon_code,
                'paid_amount' => (int) $result['import_coupon']->paid_amount,
                'debt_amount' => (int) $result['import_coupon']->debt_amount,
                'payment_status' => $result['import_coupon']->payment_status,
            ],
            'summary' => $result['summary'],
        ]);
    }
}
