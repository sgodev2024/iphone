<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierPaymentRequest;
use App\Services\SupplierPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SupplierPaymentController extends Controller
{
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
}
