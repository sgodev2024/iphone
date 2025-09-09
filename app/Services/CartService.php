<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CartService
{
    protected $cart;
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function getAllCart()
    {
        try {
            $cart = $this->cart->all();
            return $cart;
        } catch (Exception $e) {
            Log::error('Failed to fetch cart: ' . $e->getMessage());
            throw new Exception('Failed to fetch categories');
        }
    }

    public function getCartByUser()
    {
        try {
            $user_id = Auth::user()->id;
            $cart = $this->cart->where('user_id', $user_id)->get();
            return $cart;
        } catch (Exception $e) {
            Log::error('Failed to fetch cart: ' . $e->getMessage());
            throw new Exception('Failed to fetch categories');
        }
    }
    public function addCart($data)
    {
        DB::beginTransaction();
        try {
            Log::info('Creating new cart');
            $cart = Cart::where('product_id', $data['product_id'])->where('user_id', $data['user_id'])->first();
            if (!$cart) {
                $data['amount'] = 1;
                $cart = $this->cart->create($data);
                DB::commit();
                return $cart;
            } else {
                $cart->amount = $data['amount'] + $cart->amount ?? 1;
                $cart->save();
                DB::commit();
                return $cart;
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create cart: ' . $e->getMessage());
            throw new Exception('Failed to create cart');
        }
    }

    public function deleteCart(int $id)
    {
        DB::beginTransaction();
        try {
            $cart = $this->cart->findOrFail($id);
            $cart->delete();
            DB::commit();
            return $cart;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete cart: ' . $e->getMessage());
            throw new Exception('Failed to delete cart');
        }
    }

    public function clearCartUser()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not authenticated');
            }
            $user_id = $user->id;
            $cart = $this->cart->where('user_id', $user_id)->get();
            $cart->delete();
            return $cart;
        } catch (Exception $e) {
            Log::error('Failed to delete cart: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete cart'], 500);
        }
    }

    public function updateCart($id, $data)
    {
        DB::beginTransaction();
        try {
            $cart = Cart::find($id);
            if (!$cart) {

            }
            $cart->update(
                [
                    'amount' => $data['amount'],
                ]
            );
            DB::commit();
            return $cart;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete cart: ' . $e->getMessage());
            throw new Exception('Failed to delete cart');
        }
    }



}
