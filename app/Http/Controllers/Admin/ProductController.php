<?php

namespace App\Http\Controllers\Admin;

use App\Models\Categories;
use Exception;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Brand;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $title = 'Sản phẩm';
        if ($request->ajax()) {
            $searchText = $request->input('s');

            $products = Product::query()
                ->where('user_id', Auth::id())
                ->when(!empty($searchText), function ($query) use ($searchText) {
                    $query->where('name', 'like', "%$searchText%");
                })
                ->latest()
                ->paginate(10)
                ->appends($request->query());

            $html = view('admin.product.table', compact('products'))->render();
            return successResponse(data: ['html' => $html], isToastr: false);
        }

        return view('admin.product.index', compact('title'));
    }

    public function create()
    {
        $title = 'Thêm sản phẩm';
        $categories = Categories::query()->latest()->pluck('name', 'id')->toArray();
        $brands = Brand::query()->latest()->pluck('name', 'id')->toArray();
        $product = null;
        return view('admin.product.form', compact('title', 'categories', 'brands', 'product'));
    }

    public function store(ProductRequest $request)
    {
        return transaction(function () use ($request) {

            $credentials = $request->validated();

            if ($request->hasFile('thumbnail')) {
                $credentials['thumbnail'] = uploadImages('thumbnail', 'products');
            }

            $credentials['user_id'] = Auth::id();
            $credentials['code'] = generateCode('products', 'SP');

            Product::create($credentials);

            return successResponse("Thêm mới sản phẩm thành công.", code: Response::HTTP_CREATED);
        });
    }

    public function edit(string $id)
    {
        $product = Product::query()->where('user_id', Auth()->id())->with(['category', 'brand'])->findOrFail($id);
        $title = "Cập nhật sản phẩm - {$product->name}";
        $categories = Categories::query()->latest()->pluck('name', 'id')->toArray();
        $brands = Brand::query()->latest()->pluck('name', 'id')->toArray();
        return view('admin.product.form', compact('title', 'categories', 'brands', 'product'));
    }

    public function update(ProductRequest $request, $id)
    {
        return transaction(function () use ($request, $id) {
            $product = Product::query()->where('user_id', Auth()->id())->findOrFail($id);

            $oldThumbnail = $product->thumbnail;

            $credentials = $request->validated();

            if ($request->hasFile('thumbnail')) {
                $credentials['thumbnail'] = uploadImages('thumbnail', 'products');
            }

            $credentials['is_featured'] ??= 0;

            $updated =  $product->update($credentials);

            if ($updated && $request->hasFile('thumbnail')) {
                deleteImage($oldThumbnail);
            }

            return successResponse("Cập nhật sản phẩm thành công.");
        });
    }

    public function import(Request $request) {}

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $products = Product::all();
        // Đặt tiêu đề cột
        $sheet->setCellValue('A1', 'Mã sản phẩm');
        $sheet->setCellValue('B1', 'tên sản phẩm');
        $sheet->setCellValue('C1', 'Số lương');
        $sheet->setCellValue('D1', 'Giá nhập');
        $sheet->setCellValue('E1', 'Giá bán');
        $sheet->setCellValue('F1', 'Danh mục');
        $sheet->setCellValue('G1', 'Thương hiệu');
        $sheet->setCellValue('H1', 'Đơn vị');

        // Lấy danh sách sản phẩm


        // Điền dữ liệu vào sheet
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->code);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->quantity);
            $sheet->setCellValue('D' . $row, $product->price);
            $sheet->setCellValue('E' . $row, $product->price_buy);
            $sheet->setCellValue('F' . $row, $product->category->name);
            $sheet->setCellValue('G' . $row, $product->brands->name);
            $sheet->setCellValue('H' . $row, $product->product_unit);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);

        // Tạo file Excel và lưu vào output stream
        $writer = new Xlsx($spreadsheet);

        // Đặt tên file
        $fileName = 'products.xlsx';

        // Trả về file dưới dạng download response
        $response = response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );

        return $response;
    }
}
