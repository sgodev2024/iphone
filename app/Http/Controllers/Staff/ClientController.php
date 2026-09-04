<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Client;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class ClientController extends Controller
{
    public function __construct(private BranchContext $branchContext)
    {
    }

    public function addClient(Request $request)
    {
        $user = Auth::user();
        $userId = $user->isStaff() ? $user->manager_id : $user->id;

        $branchId = $user->isAdministrator()
            ? $request->integer('branch_id')
            : $this->branchContext->branchId($user);
        $branchRule = $user->isAdministrator()
            ? ['required', 'integer', 'exists:branches,id']
            : ['prohibited'];

        $data = Validator::make($request->all(), [
            'name' => ['required', 'max:255'],
            'phone' => [
                'required',
                'max:11',
                'min:10',
                Rule::unique('clients', 'phone')
                    ->where(fn ($query) => $query->where('branch_id', $branchId)),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'max:255'],
            'gender' => ['nullable', 'in:Male,Female'],
            'dob' => ['nullable', 'date'],
            'clientgroup_id' => ['nullable', 'integer', 'exists:client_group,id'],
            'branch_id' => $branchRule,
        ], __('request.messages'), [
            'name' => 'Tên khách hàng',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'address' => 'Địa chỉ',
        ]);

        if ($data->fails()) {
            return response()->json([
                'message' => $data->errors()->first(),
                'errors' => $data->errors(),
            ], HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $data->validate();
        $credentials['user_id'] = $userId;
        $credentials['branch_id'] = $branchId;
        $client = Client::create($credentials);

        return response()->json([
            'message' => 'Tạo mới khách hàng thành công.',
            'data' => $client,
        ], HttpFoundationResponse::HTTP_CREATED);
    }

    public function cart()
    {
        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)->get();
        $totalAmount = $cartItems->sum(fn ($item) => $item->amount * $item->price);

        return response()->json([
            'cart' => $cartItems,
            'totalAmount' => $totalAmount,
        ]);
    }
}
