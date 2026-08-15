<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerDebtCollectionRequest;
use App\Http\Requests\Admin\StoreCustomerDebtPaymentRequest;
use App\Models\Client;
use App\Services\CustomerDebtCollectionService;
use App\Services\CustomerDebtPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CustomerDebtPaymentController extends Controller
{
    public function preview(
        Request $request,
        int $clientId,
        CustomerDebtCollectionService $service
    ): JsonResponse {
        abort_unless($request->user()?->hasPermission('receipt.create'), Response::HTTP_FORBIDDEN);

        try {
            return response()->json($service->preview(
                $request->user(),
                $clientId,
                $request->filled('amount') ? (string) $request->input('amount') : null,
                $request->filled('collection_date') ? (string) $request->input('collection_date') : null
            ));
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'blocked',
                'can_collect' => false,
                'blocked_reason' => collect($exception->errors())->flatten()->first(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function clients(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('receipt.create'), Response::HTTP_FORBIDDEN);

        $keyword = trim((string) $request->query('keyword'));

        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $clients = Client::query()
            ->where('user_id', $request->user()->ownerId())
            ->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'code', 'name', 'phone'])
            ->map(fn (Client $client): array => [
                'id' => (int) $client->id,
                'code' => $client->code,
                'name' => $client->name,
                'phone' => $client->phone,
            ]);

        return response()->json($clients);
    }

    public function storeCollection(
        StoreCustomerDebtCollectionRequest $request,
        CustomerDebtCollectionService $service
    ): JsonResponse {
        $payload = $request->validated();
        $storedAttachment = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $storedAttachment = $file->storeAs(
                'attachments/customer_debt_collections',
                $filename,
                'public'
            );
            $payload['attachment'] = $storedAttachment;
        }

        try {
            $result = $service->collect($request->user(), $payload);
        } catch (Throwable $exception) {
            if ($storedAttachment !== null) {
                Storage::disk('public')->delete($storedAttachment);
            }

            throw $exception;
        }

        $collection = $result['collection'];

        if ($result['replayed'] && $storedAttachment !== null && $collection->attachment !== $storedAttachment) {
            Storage::disk('public')->delete($storedAttachment);
        }

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
