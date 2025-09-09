<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ZaloOa;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZaloController extends Controller
{
    public function index()
    {
        $connectedApps = ZaloOa::all();

        return view('superadmin.zalo.oa', compact('connectedApps'));
    }
    public function updateOaStatus($oaId)
    {
        try {
            // Vô hiệu hóa tất cả các OA khác
            ZaloOa::query()->update(['is_active' => 0]);

            // Kích hoạt OA được chọn
            $activeOa = ZaloOa::where('oa_id', $oaId)->first();
            $activeOa->is_active = 1;
            $activeOa->save();

            return response()->json([
                'success' => true,
                'message' => 'Trạng thái OA được cập nhật thành công.',
                'activeOaName' => $activeOa->name,
                'accessToken' => $activeOa->access_token,
                'refreshToken' => $activeOa->refresh_token
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật trạng thái OA.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function refreshAccessToken()
    {
        try {
            $activeOa = ZaloOa::where('is_active', 1)->first();

            if (!$activeOa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy OA đang kích hoạt.'
                ]);
            }

            $refreshToken = $activeOa->refresh_token;
            $secretKey = env('ZALO_APP_SECRET');
            $appId = env('ZALO_APP_ID');

            $client = new Client();
            $response = $client->post('https://oauth.zaloapp.com/v4/oa/access_token', [
                'headers' => [
                    'secret_key' => $secretKey,
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'app_id' => $appId,
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['access_token'])) {
                // Cập nhật access token vào cơ sở dữ liệu
                $activeOa->access_token = $body['access_token'];
                if (isset($body['refresh_token'])) {
                    $activeOa->refresh_token = $body['refresh_token'];
                }
                $activeOa->save();

                // Cập nhật access token vào cache
                Cache::put('access_token', $body['access_token'], 86400); // 24 giờ
                Cache::put('access_token_expiration', now()->addHours(24), 86400);

                if (isset($body['refresh_token'])) {
                    Cache::put('refresh_token', $body['refresh_token'], 7776000); // 90 ngày
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Access token đã được làm mới và lưu vào cache thành công.',
                    'new_access_token' => $body['access_token'],
                    'new_refresh_token' => $body['refesh_token']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể làm mới access token.'
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi làm mới access token.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getActiveOaName()
    {
        $activeOa = ZaloOa::where('is_active', 1)->first();

        if ($activeOa) {
            return response()->json(['active_oa_name' => $activeOa->name]);
        }

        return response()->json(['active_oa_name' => null]);
    }
}
