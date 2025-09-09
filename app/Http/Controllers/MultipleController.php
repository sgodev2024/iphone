<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Categories;
use App\Models\CheckInventory;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Import;
use App\Models\ImportCoupon;
use App\Models\Receipts;
use App\Models\Storage;
use Illuminate\Http\Request;

class MultipleController extends Controller
{
    //
    public function deleteMultiple(Request $request)
    {
        $modelName = $request->input('model');
        $ids = $request->input('ids');

        if (empty($modelName) || empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        }

        $allowedModels = [
            'Product' => \App\Models\Product::class,
            'User' => \App\Models\User::class,
            'Categories' => Categories::class,
            'Brand' => Brand::class,
            'Company' => Company::class,
            'Storage' => Storage::class,
            'ImportCoupon' => ImportCoupon::class,
            'Client' => Client::class,
            'Receipts' => Receipts::class,
            'Expense' => Expense::class,
            'CheckInventory' => CheckInventory::class
        ];

        if (!array_key_exists($modelName, $allowedModels)) {
            return response()->json(['success' => false, 'message' => 'Model không hợp lệ']);
        }

        $modelClass = $allowedModels[$modelName];

        try {
            $modelClass::whereIn('id', $ids)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
