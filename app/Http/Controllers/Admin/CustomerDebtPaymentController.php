<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerDebtCollectionRequest;
use App\Http\Requests\Admin\StoreCustomerDebtPaymentRequest;
use App\Services\CustomerDebtCollectionService;
use App\Services\CustomerDebtPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerDebtPaymentController extends Controller
{
    public function preview(
        Request $request,
        int $clientId,
        CustomerDebtCollectionService $service
    ): JsonResponse {
        abort_unless($request->user()?->hasPermission('receipt.create'), Response::HTTP_FORBIDDEN);

        return response()->json($service->preview(
            $request->user(),
            $clientId,
            $request->filled('amount') ? (string) $request->input('amount') : null,
            $request->filled('collection_date') ? (string) $request->input('collection_date') : null
        ));
    }

    public function storeCollection(
        StoreCustomerDebtCollectionRequest $request,
        CustomerDebtCollectionService $service
    ): JsonResponse {
        $result = $service->collect($request->user(), $request->validated());
        $collection = $result['collection'];

        return response()->json([
            'message' => $result['replayed']
                ? 'Yêu cầu đã được xử lý trước đó.'
                : 'Thu tổng công nợ khách hàng thành công.',
            'replayed' => $result['replayed'],
            'collection' => [
                'id' => (int) $collection->id,
                'collection_number' => $collection->collection_number,
                'total_amount' => $collection->total_amount,
                'status' => $collection->status,
                'allocations' => $collection->allocations->map(fn ($allocation): array => [
                    'order_id' => (int) $allocation->order_id,
                    'allocated_amount' => $allocation->allocated_amount,
                    'remaining_after' => $allocation->remaining_after,
                    'payment_transaction_id' => (int) $allocation->payment_transaction_id,
                ])->values(),
            ],
            'collectible_total' => $result['collectible_total'],
        ]);
    }

    public function options(
        Request $request,
        int $clientId,
        CustomerDebtPaymentService $service
    ): JsonResponse {
        abort_unless($request->user()?->hasPermission('receipt.create'), Response::HTTP_FORBIDDEN);

        return response()->json([
            'orders' => $service->outstandingOrders($request->user(), $clientId),
            'bank_accounts' => $service->bankAccounts(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(
        StoreCustomerDebtPaymentRequest $request,
        CustomerDebtPaymentService $service
    ): JsonResponse {
        $result = $service->collect($request->user(), $request->validated());

        return response()->json([
            'message' => $result['replayed']
                ? 'Yêu cầu đã được xử lý trước đó.'
                : 'Thu công nợ thành công.',
            'replayed' => $result['replayed'],
            'transaction_id' => (int) $result['transaction']->id,
            'order' => [
                'id' => (int) $result['order']->id,
                'paid_amount' => (int) $result['order']->paid_amount,
                'debt_amount' => (int) $result['order']->debt_amount,
                'payment_status' => $result['order']->payment_status,
            ],
        ]);
    }

    public function legacyReceiptRedirect(): RedirectResponse
    {
        return redirect()
            ->route('admin.debts.customer')
            ->with('warning', 'Vui lòng thu công nợ từ báo cáo công nợ khách hàng.');
    }

    public function legacyWriteDisabled(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đường ghi legacy đã bị đóng. Vui lòng dùng luồng thu công nợ theo order.',
            ], Response::HTTP_GONE);
        }

        return redirect()
            ->route('admin.debts.customer')
            ->withErrors('Đường ghi legacy đã bị đóng. Vui lòng dùng luồng thu công nợ theo order.');
    }

    public function legacyPosDisabled(): JsonResponse
    {
        return response()->json([
            'message' => 'Đường bán hàng legacy đã bị đóng. Vui lòng dùng checkout hiện tại.',
        ], Response::HTTP_GONE);
    }
}
