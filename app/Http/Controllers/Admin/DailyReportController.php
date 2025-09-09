<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\DailyReportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DailyReportController extends Controller
{
    protected $dailyReport;
    public function __construct(DailyReportService $dailyReport)
    {
        $this->dailyReport = $dailyReport;
    }
    public function getDailyOrderData()
    {
        try {
            $reportData = $this->dailyReport->getDailyOrder();

            // Generate Excel file
            $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $excel->getActiveSheet();

            // Add orders data
            $sheet->setCellValue('A1', 'Mã đơn hàng');
            $sheet->setCellValue('B1', 'Nhân viên');
            $sheet->setCellValue('C1', 'Ngày tạo');
            $sheet->setCellValue('D1', 'Khách hàng');
            $sheet->setCellValue('E1', 'Trạng thái');
            $sheet->setCellValue('F1', 'Tổng tiền');

            $row = 2;
            foreach ($reportData['orders'] as $order) {
                $sheet->setCellValue('A' . $row, $order->id);
                $sheet->setCellValue('B' . $row, $order->user->name ?? '');
                $sheet->setCellValue('C' . $row, $order->created_at->format('d/m/y'));
                $sheet->setCellValue('D' . $row, $order->client->name ?? '');
                $sheet->setCellValue('E' . $row, $order->status == 1 ? 'Đã thanh toán' : 'Công nợ');
                $sheet->setCellValue('F' . $row, number_format($order->total_money));
                $row++;
            }

            // Add product sales data
            $sheet->setCellValue('H1', 'Tên sản phẩm');
            $sheet->setCellValue('I1', 'Số lượng');
            $sheet->setCellValue('J1', 'Tổng tiền');

            $row = 2;
            foreach ($reportData['productSales'] as $productId => $sales) {
                $product = $reportData['products']->get($productId);
                if ($product) {
                    $sheet->setCellValue('H' . $row, $product->name);
                    $sheet->setCellValue('I' . $row, $sales['quantity']);
                    $sheet->setCellValue('J' . $row, number_format($sales['total']));
                    $row++;
                }
            }

            // Save Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel);
            $filename = 'daily_report.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), $filename);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Failed to export daily report: " . $e->getMessage());
            return response()->json(['error' => 'Failed to export daily report'], 500);
        }
    }

    public function getDailyOrder()
    {
        try {
            $reportData = $this->dailyReport->getDailyOrder();
            return view('admin.report.order', $reportData);
        } catch (Exception $e) {
            Log::error("Failed to get today's report: " . $e->getMessage());
            return ApiResponse::error("Failed to get today's report", 500);
        }
    }

    public function getDailyImport()
    {
        try {
            $reportData = $this->dailyReport->getDailyImport();
            return view('admin.report.import', $reportData);
        } catch (Exception $e) {
            Log::error("Failed to get today's Importation: " . $e->getMessage());
            return response()->json(['error' => "Failed to get today's Importation"], 500);
        }
    }

    public function getDailyImportData()
    {
        try {
            // Lấy dữ liệu báo cáo từ service
            $reportData = $this->dailyReport->getDailyImport();

            // Tạo đối tượng Spreadsheet mới
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();

            // Đặt tiêu đề cho bảng đơn nhập hàng
            $sheet->setCellValue('A1', 'Mã đơn hàng');
            $sheet->setCellValue('B1', 'Nhân viên');
            $sheet->setCellValue('C1', 'Ngày tạo');
            $sheet->setCellValue('D1', 'Nhà cung cấp');
            $sheet->setCellValue('E1', 'Trạng thái');
            $sheet->setCellValue('F1', 'Tổng tiền');

            // Điền dữ liệu đơn nhập hàng
            $row = 2;
            foreach ($reportData['imports'] as $import) {
                $sheet->setCellValue('A' . $row, $import->coupon_code);
                $sheet->setCellValue('B' . $row, $import->user->name ?? '');
                $sheet->setCellValue('C' . $row, $import->created_at->format('d/m/y'));
                $sheet->setCellValue('D' . $row, $import->company->name ?? '');
                $sheet->setCellValue('E' . $row, $import->status == 1 ? 'Đã thanh toán' : 'Công nợ');
                $sheet->setCellValue('F' . $row, number_format($import->total));
                $row++;
            }

            // Đặt tiêu đề cho bảng sản phẩm nhập hàng
            $sheet->setCellValue('H1', 'Tên sản phẩm');
            $sheet->setCellValue('I1', 'Số lượng');
            $sheet->setCellValue('J1', 'Giá nhập cũ');
            $sheet->setCellValue('K1', 'Giá nhập mới');
            $sheet->setCellValue('L1', 'Tổng tiền');

            // Điền dữ liệu sản phẩm nhập hàng
            $row = 2;
            // Trong phương thức getDailyImportData của Controller
            foreach ($reportData['productImports'] as $productId => $imports) {
                $product = $reportData['products']->get($productId);
                if ($product) {
                    // Log dữ liệu để kiểm tra
                    Log::info('Product ID:', ['product_id' => $productId]);
                    Log::info('Product Data:', $imports);

                    // Kiểm tra và lấy giá nhập cũ và mới
                    $oldPrice = isset($imports['old_price']) ? number_format($imports['old_price']) : 'N/A';
                    $newPrice = isset($imports['price']) ? number_format($imports['price']) : 'N/A';

                    $sheet->setCellValue('H' . $row, $product->name);
                    $sheet->setCellValue('I' . $row, $imports['quantity']);
                    $sheet->setCellValue('J' . $row, $oldPrice);
                    $sheet->setCellValue('K' . $row, $newPrice);
                    $sheet->setCellValue('L' . $row, number_format($imports['total']));
                    $row++;
                }
            }


            // Ghi Spreadsheet vào file tạm
            $writer = new Xlsx($excel);
            $filename = 'daily_importation.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), $filename);
            $writer->save($tempFile);

            // Trả file như một phản hồi tải về và xóa sau khi gửi
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            // Ghi log lỗi và trả phản hồi JSON với mã lỗi 500
            Log::error("Failed to export daily importation: " . $e->getMessage());
            return response()->json(['error' => 'Failed to export daily importation'], 500);
        }
    }
}
