<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDebtsDetail;
use App\Models\ReceiptDetail;
use App\Models\Receipts;
use App\Models\Supplier;
use App\Services\ClientService;
use App\Services\DebtKHService;
use App\Services\ReceiptsService;
use App\Services\SupplierService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    protected $receiptsService;
    protected $clientService;
    protected $debtKHService;
    public function __construct(ReceiptsService $receiptsService, ClientService $clientService, DebtKHService $debtKHService){
        $this->receiptsService = $receiptsService;
        $this->clientService = $clientService;
        $this->debtKHService = $debtKHService;

    }

    public function index(){
        $title = 'Quản lý thu';
        $receipts = Receipts::orderByDesc('updated_at')->paginate(10);
        $debtClient = $this->debtKHService->getAllClientDebt();
        return view('admin.quanlythuchi.receipt.index', compact('receipts', 'title', 'debtClient'));
    }

    public function add(){
        $title = 'Quản lý thu';
        $debtClient = $this->debtKHService->getAllClientDebt();
        return view('admin.quanlythuchi.receipt.add', compact('title', 'debtClient'));
    }

    public function addSubmit(Request $request){
        $client = Client::find($request->client);
        $receipts = $this->receiptsService->getAllReceipts()->pluck('client_id');
        if($receipts->contains($request->client)){
            $receipt = $this->receiptsService->findRecieptByClient($request->client);
            $expensedata = [
                'amount_spent' => $request->amount_spent + $receipt->amount_spent,
            ];
            $this->receiptsService->updateReceipt($expensedata, $request->client);
            ReceiptDetail::create([
                'receipt_id' => $receipt->id,
                'content' => $request->content,
                'amount' => $request->amount_spent,
                'date' => Carbon::now()->toDateString(),
            ]);
        }else{
            $add = [
                'client_id' => $request->client,
                'content' => 'Khách hàng thanh toán '.$client->name,
                'amount_spent' => $request->amount_spent,
                'date_spent' => Carbon::now()->toDateString(),
            ];
            $receipt = $this->receiptsService->addReceipts($add);
            ReceiptDetail::create([
                'receipt_id' => $receipt->id,
                'content' => $request->content,
                'amount' => $request->amount_spent,
                'date' => Carbon::now()->toDateString(),
            ]);
        }
        $debtclient = $this->debtKHService->findClientDebtByClient($request->client);
        $update = [
            'amount' => $debtclient->amount - $request->amount_spent,
        ];
        ClientDebtsDetail::create([
            'customer_debts_id' => $debtclient->id,
            'content' => 'Tạo phiếu thu',
            'amount' => 0 - $request->amount_spent,
        ]);
        $this->debtKHService->updateClientDebt($update, $request->client);
        $debtnew = $this->debtKHService->findClientDebtByClient($request->client);
        if($debtnew->amount == 0){
            ClientDebtsDetail::truncate();
            $this->debtKHService->delete($request->client);
        }
        return redirect()->route('admin.quanlythuchi.receipts.index')->with('success', 'Tạo phiếu thành công !');
    }


    public function detail($id){
        $title = 'Quản lý thu';
        $receipts = $this->receiptsService->findReceiptById($id);
        return view('admin.quanlythuchi.receipt.detail', compact('receipts', 'title'));
    }

    public function debt(Request $request){
        $client = $request->client;
        $debt = $this->debtKHService->findClientDebtByClient($client);
        return  response()->json(explode(',', $debt->amount)[0]);
    }


}
