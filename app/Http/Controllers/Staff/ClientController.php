<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\NumberToWords;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use App\Models\Client;
use App\Models\ClientDebtsDetail;
use App\Models\Config;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ReceiptDetail;
use App\Services\ClientService;
use App\Services\DebtKHService;
use App\Services\ProductService;
use App\Services\ProductStorageService;
use App\Services\ReceiptsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Kavenegar;


// Import the PDF facade
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

// Import the Storage facade

class ClientController extends Controller
{
    //
    protected $clientService;
    protected $productService;
    protected $receiptsService;
    protected $debtKHService;
    protected $productStorageService;
    public function __construct(ClientService $clientService, ProductService $productService, ReceiptsService $receiptsService, DebtKHService $debtKHService, ProductStorageService $productStorageService)
    {
        $this->clientService = $clientService;
        $this->productService = $productService;
        $this->receiptsService = $receiptsService;
        $this->debtKHService = $debtKHService;
        $this->productStorageService = $productStorageService;
    }

    public function addClient(Request $request)
    {
        $user = Auth::user();

        $userId = $user->role_id === 3 ? $user->manager_id : $user->id;

        $data = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:clients,email,' . $userId,
            'phone' => 'required|max:11|min:10|unique:clients,phone,' . $userId,
            'address' => 'nullable|max:255'
        ], __('request.messages'), [
            'name' => 'Tên khách hàng',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'address' => 'Địa chỉ'
        ]);

        if ($data->fails()) {
            return response()->json([
                'message' => $data->errors()->first(),
            ], HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $data->validate();

        $credentials['user_id'] = $userId;

        $client =  Client::create($credentials);

        return response()->json([
            "message" => 'Tạo mới khách hàng thành công.',
            'data' => $client
        ], HttpFoundationResponse::HTTP_CREATED);
    }

    public function submitOrder(Request $request)
    {
        try {
            $user = Auth::user();
            $listphone = $this->clientService->getAllClientStaff()->pluck('phone');
            if ($listphone->contains($request->phone)) {
                $client = $this->clientService->findClientByPhone($request->phone);
                $cartItems = Cart::where('user_id', $user->id)->get();
            } else {
                $client = $this->clientService->addClient($request->all());
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch clients: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch clients', 500);
        }
    }


    public function bill($file)
    {
        $response = Response::download(public_path($file))->deleteFileAfterSend(true);

        return $response;
    }
    public function pay(Request $request)
    {

        // try {
        $user = Auth::user();
        $storageId = $user->storage_id;
        $listphone = $this->clientService->getAllClientStaff()->pluck('phone');
        $cartItems = Cart::where('user_id', $user->id)->get();
        $sum = 0;
        $client = array();
        $trangthai = $request->status;

        foreach ($cartItems as $key => $item) {
            $sum += $item->price * $item->amount;
            // $this->productService->updateProductAmount($item->product_id, ['quantity' => $item->product->quantity - $item->amount]);
            $productupdate = Product::find($item->product_id)->update(['quantity' => $item->product->quantity - $item->amount]);
        }

        if ($listphone->contains($request->phone)) {
            $client = $this->clientService->findClientByPhone($request->phone);
            $order = Order::create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'total_money' => $sum,
                'status' => $request->status,
                'notification' => 1
            ]);
            foreach ($cartItems as $key => $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'quantity' => $item->amount,
                    'product_id' => $item->product_id,
                    'storage_id' => $user->storage_id,
                    'price' => $item->price
                ]);

                $this->productStorageService->updateProductAmount($item->product_id, $storageId, $item->amount);
            }
        } else {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'phone' => $request->phone,
                'clientgroup_id' => $request->clientgroup_id ?? 3
            ];
            $client = $this->clientService->addClient($request->all());
            $order = Order::create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'total_money' => $sum,
                'status' => $trangthai,
                'notification' => 1
            ]);
            foreach ($cartItems as $key => $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'quantity' => $item->amount,
                    'storage_id' => $user->storage_id,
                    'product_id' => $item->product_id,
                    'price' => $item->price
                ]);

                $this->productStorageService->updateProductAmount($item->product_id, $storageId, $item->amount);
            }
        }

        if ($trangthai != 4) {
            $listreceipt = $this->receiptsService->getAllReceipts()->pluck('client_id');
            if ($listreceipt->contains($client->id)) {
                $receipt = $this->receiptsService->findRecieptByClient($client->id);
                $data1 = [
                    'amount_spent' => $sum + $receipt->amount_spent,
                    'date_spent' => Carbon::now()->toDateString()
                ];
                $this->receiptsService->updateReceipt($data1, $client->id);
                $detail = [
                    'receipt_id' => $receipt->id,
                    'content' => 'Thu từ khách hàng có số điện thoại ' . $request->phone,
                    'amount' => $sum,
                    'date' => Carbon::now()->toDateString()
                ];
                ReceiptDetail::create($detail);
            } else {
                $data1 = [
                    'client_id' => $client->id,
                    'content' => 'Thu từ khách hàng có số điện thoại ' . $request->phone,
                    'amount_spent' => $sum,
                    'date_spent' => Carbon::now()->toDateString()
                ];
                $receipt = $this->receiptsService->addReceipts($data1);
                $detail = [
                    'receipt_id' => $receipt->id,
                    'content' => 'Thu từ khách hàng có số điện thoại ' . $request->phone,
                    'amount' => $sum,
                    'date' => Carbon::now()->toDateString()
                ];
                ReceiptDetail::create($detail);
            }
        } else {
            $ClientDebt = $this->debtKHService->getAllClientDebt()->pluck('client_id');
            if ($ClientDebt->contains($client->id)) {
                $clientdebt = $this->debtKHService->findClientDebtByClient($client->id);
                $data2 = [
                    'amount' => $clientdebt->amount + $sum,
                ];
                ClientDebtsDetail::create([
                    'customer_debts_id' => $clientdebt->id,
                    'content' => 'Giao dịch thành công ',
                    'amount' => $sum,
                ]);
                $this->debtKHService->updateClientDebt($data2, $client->id);
            } else {
                $data = [
                    'client_id' => $client->id,
                    'amount' => $sum,
                    'description' => 'Khách hàng có số điện thoại ' . $request->phone,
                ];
                $ClientDebt = $this->debtKHService->addClientDebt($data);
                ClientDebtsDetail::create([
                    'customer_debts_id' => $ClientDebt->id,
                    'content' => 'Giao dịch thành công ',
                    'amount' => $sum,
                ]);
            }
        }

        Cart::where('user_id', $user->id)->delete();
        $config = Config::first();
        $text = convert($sum);
        $html = view('Themes.pages.bill.index', compact('cartItems', 'sum', 'client', 'user', 'config', 'text'))->render();
        $pdf = Pdf::loadHTML($html);
        $pdfFileName = 'order.pdf';
        $pdf->save(public_path($pdfFileName));
        $response = $this->bill($pdfFileName);
        Session::flash('action', 'Thanh toán thành công');

        return response()->json([
            'pdf_url' => asset($pdfFileName),
            'message' => 'Thanh toán thành công'
        ]);
        // } catch (Exception $e) {
        //     Log::error('Failed to process payment: ' . $e->getMessage());
        //     return response()->json(['error' => 'Failed to process payment'], 500);
        // }
    }


    public function cart()
    {
        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)->get();

        $totalAmount = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price_buy;
        });

        // Trả về dữ liệu giỏ hàng và tổng tiền dưới dạng JSON
        return response()->json([
            'cart' => $cartItems,
            'totalAmount' => $totalAmount,
        ]);
    }
}
