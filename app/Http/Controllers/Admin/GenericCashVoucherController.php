<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGenericCashVoucherRequest;
use App\Models\CashVoucher;
use App\Services\GenericCashVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenericCashVoucherController extends Controller
{
    public function store(
        StoreGenericCashVoucherRequest $request,
        GenericCashVoucherService $service
    ): JsonResponse {
        $voucher = $service->create($request->user(), $request->validated());
        $kind = $voucher->direction === CashVoucher::DIRECTION_RECEIPT ? 'thu' : 'chi';

        return response()->json([
            'success' => true,
            'message' => "Đã tạo phiếu {$kind} tiền mặt {$voucher->voucher_number}. Trạng thái: Chờ hạch toán.",
            'voucher' => [
                'id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'accounting_status' => $voucher->accounting_status,
                'accounting_status_label' => 'Chờ hạch toán',
            ],
            'redirect' => route('admin.transactions.cash.vouchers.show', $voucher),
        ], 201);
    }

    public function show(CashVoucher $voucher)
    {
        $voucher = $this->ownedVoucher($voucher)->load(['cashAccount', 'creator']);

        return view('admin.cash-bank.voucher-detail', compact('voucher'));
    }

    public function attachment(CashVoucher $voucher): BinaryFileResponse|Response
    {
        $voucher = $this->ownedVoucher($voucher);
        abort_unless($voucher->attachment && Storage::disk('public')->exists($voucher->attachment), 404);

        return response()->file(Storage::disk('public')->path($voucher->attachment));
    }

    private function ownedVoucher(CashVoucher $voucher): CashVoucher
    {
        abort_unless((int) $voucher->owner_id === (int) request()->user()->ownerId(), 404);

        return $voucher;
    }
}
