<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkController extends Controller
{
    public function bulk(string $type, Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'model' => 'required|string',
        ], __('request.messages'));

        $ids = $request->input('ids', []);
        $model = $request->input('model', null);

        $modelClass = '\\App\\Models\\'.$model;

        if (! class_exists($modelClass)) {
            return errorResponse('Model không tồn tại!', 400);
        }

        $ids = array_values(array_unique($ids));

        if ($type === 'delete' && is_a($modelClass, Product::class, true)) {
            $products = Product::query()
                ->where('user_id', Auth::id())
                ->whereIn('id', $ids);

            if ((clone $products)->count() !== count($ids)) {
                abort(404);
            }

            if ((clone $products)->whereHas('imeis')->exists()) {
                return errorResponse('Không thể xóa sản phẩm vì sản phẩm đang có dữ liệu IMEI.', 422);
            }
        }

        return transaction(function () use ($modelClass, $ids, $type) {
            switch ($type) {
                case 'delete':
                    $modelClass::whereIn('id', $ids)->delete();

                    return response()->json(['message' => 'Xóa thành công!']);

                case 'status':
                    $modelClass::whereIn('id', $ids)
                        ->update(['status' => DB::raw('NOT status')]);

                    return successResponse('Cập nhật trạng thái thành công!');

                default:
                    return errorResponse('Hành động không hợp lệ!', 400);
            }
        });
    }
}
