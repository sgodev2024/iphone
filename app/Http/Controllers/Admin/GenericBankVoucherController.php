<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGenericBankVoucherRequest;
use App\Models\BankVoucher;
use App\Services\GenericBankVoucherService;
use App\Support\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenericBankVoucherController extends Controller
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function store(
        StoreGenericBankVoucherRequest $request,
        GenericBankVoucherService $service
    ): JsonResponse {
        $voucher = $service->create($request->user(), $request->validated());
        $kind = $voucher->direction === BankVoucher::DIRECTION_RECEIPT ? 'thu' : 'chi';

        return response()->json([
            'success' => true,
            'message' => "Đã tạo phiếu {$kind} ngân hàng {$voucher->voucher_number}. Trạng thái: Chờ hạch toán.",
            'voucher' => [
                'id' => (int) $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'amount' => $voucher->amount,
                'direction' => $voucher->direction,
                'operation' => $voucher->operation,
                'document_type' => $voucher->document_type,
                'reference_number' => $voucher->reference_number,
                'bank_account' => [
                    'id' => $voucher->bankAccount?->id,
                    'code' => $voucher->bankAccount?->code,
                    'name' => $voucher->bankAccount?->name,
                ],
                'accounting_status' => $voucher->accounting_status,
                'accounting_status_label' => 'Chờ hạch toán',
            ],
        ], 201);
    }

    public function show(BankVoucher $voucher)
    {
        $voucher = $this->ownedVoucher($voucher)->load(['bankAccount', 'creator']);

        return view('admin.cash-bank.bank-voucher-detail', compact('voucher'));
    }

    public function attachment(BankVoucher $voucher): BinaryFileResponse|Response
    {
        $voucher = $this->ownedVoucher($voucher);
        abort_unless($voucher->attachment && Storage::disk('public')->exists($voucher->attachment), 404);

        return response()->file(Storage::disk('public')->path($voucher->attachment));
    }

    private function ownedVoucher(BankVoucher $voucher): BankVoucher
    {
        abort_unless((int) $voucher->owner_id === (int) request()->user()->ownerId(), 404);
        $this->branchContext->authorize(
            request()->user(),
            $voucher->branch_id === null ? null : (int) $voucher->branch_id
        );

        return $voucher;
    }
}
