<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class ClientController extends Controller
{
    public function addClient(Request $request)
    {
        $user = Auth::user();
        $userId = $user->role_id === 3 ? $user->manager_id : $user->id;

        $data = Validator::make($request->all(), [
            'name' => ['required', 'max:255'],
            'phone' => [
                'required',
                'max:11',
                'min:10',
                Rule::unique('clients', 'phone')
                    ->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'max:255'],
            'gender' => ['nullable', 'in:Male,Female'],
            'dob' => ['nullable', 'date'],
            'clientgroup_id' => ['nullable', 'integer', 'exists:client_group,id'],
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
