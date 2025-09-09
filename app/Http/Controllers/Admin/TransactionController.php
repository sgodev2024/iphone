<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SuperAdmin;
use App\Services\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected $transactionService;
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        try {
            $status = $request->input('status');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $authUser = session('authUser');
            $userId = $authUser->id;

            $transactions = $this->transactionService->getPaginatedTransactionsForAdmin($userId, $status, $startDate, $endDate);
            if ($request->ajax()) {
                return response()->json([
                    'html' => view('admin.transaction.table', compact('transactions'))->render(),
                    'pagination' => $transactions->links('pagination::custom')->render(),
                ]);
            }
            return view('admin.transaction.index', compact('transactions'));
        } catch (Exception $e) {
            Log::error("Failed to get paginated Transaction list: " . $e->getMessage());
            return ApiResponse::error("Failed to get paginated Transaction list", 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $status = $request->input('status');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $authUser = session('authUser');
            $userId = $authUser->id;

            $transactions = $this->transactionService->getPaginatedTransactionsForAdmin($userId, $status, $startDate, $endDate);
            if ($request->ajax()) {
                return response()->json([
                    'html' => view('admin.transaction.table', compact('transactions'))->render(),
                    'pagination' => $transactions->links('pagination::custom')->render(),
                ]);
            }
            return view('admin.transaction.index', compact('transactions'));
        } catch (Exception $e) {
            Log::error("Failed to get paginated Transaction list: " . $e->getMessage());
            return ApiResponse::error("Failed to get paginated Transaction list", 500);
        }
    }

    public function payment()
    {
        $authUser = session('authUser');

        return view('admin.transaction.payment', compact('authUser'));
    }

    public function store(Request $request)
    {
        $authUser = session('authUser');
        $transaction = $this->transactionService->createNewTransaction($request->all(), $authUser->id);
        $this->exportPDF($transaction->id);
        return redirect()->route('admin.dashboard');
    }

    public function exportPDF($id)
    {
        try{
            $transaction = $this->transactionService->getTransactionById($id);

            $pdf = Pdf::loadView('pdf.transaction', compact('transaction'));
            $fileName = 'Hóa đơn giao dịch của khách hàng '. $transaction->user->name . '.pdf';

            return $pdf->download($fileName);
        }
        catch(Exception $e)
        {
            Log::error("Failed to export this Transaction: " .$e->getMessage());
            return ApiResponse::error("Failed to export this transaction", 500);
        }
    }


    public function generateQrCode(Request $request)
    {
        $superAdmin = SuperAdmin::first();
        $amount = $request->input('amount');
        $bank_id = $superAdmin->bank->shortName;
        $bank_account = $superAdmin->bank_account;
        $description = $request->input('description');
        $account_name = $superAdmin->name;

        //Tạo URL cho QR code
        $template = 'compact';
        $qrCodeUrl = "https://img.vietqr.io/image/{$bank_id}-{$bank_account}-{$template}.png?amount={$amount}&addInfo={$description}&accountName={$account_name}";

        return view('admin.transaction.payment', compact('qrCodeUrl'));
    }
}
