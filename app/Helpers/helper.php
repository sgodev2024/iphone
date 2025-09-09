<?php

use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (!function_exists('showImage')) {
    function showImage($image)
    {
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk('public');

        if ($image && $storage->exists($image)) {
            return $storage->url($image);
        }

        return asset('assets/img/default-image.jpg');
    }
}

function deleteImage($path)
{
    if ($path && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
}

function formatPrice($price)
{
    if (!empty($price)) {
        // Làm tròn đến 0 hoặc 1 chữ số thập phân tuỳ giá trị
        $float = (float) $price;

        // Nếu là số nguyên → format không có phần thập phân
        if (floor($float) == $float) {
            return number_format($float, 0, '', '.');
        }

        // Nếu có phần thập phân → format với 1–2 số sau dấu phẩy
        return number_format($float, 2, ',', '.');
    }

    return '0';
}

if (!function_exists('transaction')) {
    function transaction($callback, $onError = null)
    {
        DB::beginTransaction();
        try {
            $result = $callback();
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            if ($onError && is_callable($onError)) {
                $onError($e);
            }

            Log::error('Exception Details:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return errorResponse('Có lỗi xảy ra, vui lòng thử lại sau!');
        }
    }
}

if (!function_exists('successResponse')) {
    function successResponse($message = 'call api successful', $data = null, $code = 200, bool $isResponse = true, bool $isToastr = true)
    {
        $response = ['success' => true, 'message' => $message, 'data' => $data, 'code' => $code];

        if ($isToastr) session()->flash('success', $message);

        return $isResponse ? response()->json($response, $code) : $response;
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse(string $message, $code = 500, bool $isResponse = true)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code
        ];
        return $isResponse ? response()->json($response, $code) : $response;
    }
}

if (!function_exists('generateCode')) {

    function generateCode($table, $prefix, $length = 10)
    {
        // Số ký tự còn lại sau prefix
        $padLength = $length - strlen($prefix);

        // Tập ký tự để random (chữ hoa + số)
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        do {
            $randomString = '';
            for ($i = 0; $i < $padLength; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }

            $code = $prefix . $randomString;
        } while (DB::table($table)->where('code', $code)->exists());

        return $code;
    }
}

if (!function_exists('uploadImages')) {
    function uploadImages($flieName, string $directory = 'images', $resize = false, $width = 150, $height = 150, $isArray = false, $quality = 80)
    {
        $paths = [];

        $images = request()->file($flieName);
        if (!is_array($images)) {
            $images = [$images];
        }

        $manager = new ImageManager(['driver' => 'gd']);
        $storagePath = storage_path('app/public/' . trim($directory, '/'));

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        foreach ($images as $key => $image) {
            if ($image instanceof \Illuminate\Http\UploadedFile) {
                $img = $manager->make($image->getRealPath());

                // Resize nếu $resize = true, giữ tỷ lệ
                if ($resize) {
                    $img->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize(); // Không phóng to ảnh nhỏ
                    });
                }

                $filename = time() . uniqid() . '.webp';

                // Encode với chất lượng 80 (bạn có thể chỉnh từ 60 đến 90)
                Storage::disk('public')->put($directory . '/' . $filename, $img->encode('webp', $quality));

                $paths[$key] = $directory . '/' . $filename;
            }
        }

        return $isArray ? $paths : $paths[0] ?? null;
    }
}


if (!function_exists('formatPrice')) {
    function formatPrice($price)
    {
        if (!empty($price)) return 0;
        
        return number_format($price, 0, ',', '.');
    }
}
