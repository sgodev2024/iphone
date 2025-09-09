<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportdebtController extends Controller
{
    //

    public function index()
    {
        // $stocks = DB::table('products')
        //     ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_imports FROM import_detail GROUP BY product_id) as imports'), 'products.id', '=', 'imports.product_id')
        //     ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_orders FROM order_details GROUP BY product_id) as orders'), 'products.id', '=', 'orders.product_id')
        //     ->select(
        //         'products.id',
        //         'products.name',
        //         'products.code',
        //         DB::raw('
        //             COALESCE(products.quantity, 0) +
        //             COALESCE(imports.total_imports, 0) -
        //             COALESCE(orders.total_orders, 0) AS current_stock
        //         '),
        //         DB::raw('COALESCE(imports.total_imports, 0) AS total_imports'),
        //         DB::raw('COALESCE(orders.total_orders, 0) AS total_orders')
        //     )
        //     ->get();
        $title = 'Báo cáo kho';

        $today = Carbon::today();
        $startOfMonth = $today->startOfMonth()->format('Y-m-d');
        $endOfMonth = $today->endOfMonth()->format('Y-m-d');

        // Tạo subqueries cho số lượng nhập và bán
        $importsSubquery = DB::raw("
            (SELECT product_id, SUM(quantity) as total_imports
            FROM import_detail
            WHERE DATE(created_at) BETWEEN '$startOfMonth' AND '$endOfMonth'
            GROUP BY product_id) as imports
        ");

        $ordersSubquery = DB::raw("
            (SELECT product_id, SUM(quantity) as total_orders
            FROM order_details
            WHERE DATE(created_at) BETWEEN '$startOfMonth' AND '$endOfMonth'
            GROUP BY product_id) as orders
        ");

        $stocks = DB::table('products')
            ->leftJoin($importsSubquery, 'products.id', '=', 'imports.product_id')
            ->leftJoin($ordersSubquery, 'products.id', '=', 'orders.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.price_buy',
                'products.quantity',
                DB::raw('COALESCE(imports.total_imports, 0) AS total_imports'),
                DB::raw('COALESCE(orders.total_orders, 0) AS total_orders')
            )->get();
            $tongtonkho = 0;
            $tongluongnhap = 0;
            $tongluongban = 0;
            $tongtintonghang = 0;
            $tongtiendaban = 0;
            foreach($stocks as $item){
                $tongtonkho += $item->quantity;
                $tongluongnhap += $item->total_imports;
                $tongluongban += $item->total_orders;
                $tongtiendaban += $item->total_orders * $item->price_buy;
                $tongtintonghang += $item->quantity * $item->price_buy;
            }
        return view('admin.report.index', compact('stocks', 'title', 'tongtonkho', 'tongluongnhap', 'tongluongban', 'tongtiendaban', 'tongtintonghang'));
    }

    public function print(){
        $today = Carbon::today();
        $startOfMonth = $today->startOfMonth()->format('Y-m-d');
        $endOfMonth = $today->endOfMonth()->format('Y-m-d');

        // Tạo subqueries cho số lượng nhập và bán
        $importsSubquery = DB::raw("
            (SELECT product_id, SUM(quantity) as total_imports
            FROM import_detail
            WHERE DATE(created_at) BETWEEN '$startOfMonth' AND '$endOfMonth'
            GROUP BY product_id) as imports
        ");

        $ordersSubquery = DB::raw("
            (SELECT product_id, SUM(quantity) as total_orders
            FROM order_details
            WHERE DATE(created_at) BETWEEN '$startOfMonth' AND '$endOfMonth'
            GROUP BY product_id) as orders
        ");

        $stocks = DB::table('products')
            ->leftJoin($importsSubquery, 'products.id', '=', 'imports.product_id')
            ->leftJoin($ordersSubquery, 'products.id', '=', 'orders.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.price_buy',
                'products.quantity',
                DB::raw('COALESCE(imports.total_imports, 0) AS total_imports'),
                DB::raw('COALESCE(orders.total_orders, 0) AS total_orders')
            )->get();
            $tongtonkho = 0;
            $tongluongnhap = 0;
            $tongluongban = 0;
            $tongtintonghang = 0;
            $tongtiendaban = 0;
            foreach($stocks as $item){
                $tongtonkho += $item->quantity;
                $tongluongnhap += $item->total_imports;
                $tongluongban += $item->total_orders;
                $tongtiendaban += $item->total_orders * $item->price_buy;
                $tongtintonghang += $item->quantity * $item->price_buy;
            }
        return view('admin.report.print', compact('stocks','tongtonkho', 'tongluongnhap', 'tongluongban', 'tongtiendaban', 'tongtintonghang'));
    }
}
