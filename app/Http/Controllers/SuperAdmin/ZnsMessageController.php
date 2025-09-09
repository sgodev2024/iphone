<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ZaloOa;
use App\Models\ZnsMessage;
use App\Services\OaTemplateService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZnsMessageController extends Controller
{
    protected $oaTemplateService;

    public function __construct(OaTemplateService $oaTemplateService)
    {
        $this->oaTemplateService = $oaTemplateService;
    }
    public function znsMessage()
    {
        // Lấy tất cả các OA đang hoạt động
        $activeOas = ZaloOa::where('is_active', 1)->pluck('id');

        // Lấy tất cả các tin nhắn từ các OA đang hoạt động
        $messages = ZnsMessage::whereIn('oa_id', $activeOas)
            ->orderByDesc('sent_at')
            ->get();
        // dd($messages);
        // Tính tổng phí cho mỗi OA
        $totalFeesByOa = $messages->groupBy('oa_id')->map(function ($messagesByOa) {
            return $messagesByOa->sum(function ($message) {
                return $message->status == 1 ? ($message->template->price ?? 0) : 0;
            });
        });

        return view('superadmin.message.index', compact('messages', 'totalFeesByOa'));
    }



    public function znsQuota()
    {
        $accessToken = $this->getAccessToken();

        try {
            $client = new Client();
            $response = $client->get('https://business.openapi.zalo.me/message/quota', [
                'headers' => [
                    'access_token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            Log::info('Phản hồi API: ' . $responseBody);
            $responseData = json_decode($responseBody, true)['data'];
            return view('superadmin.message.quota', compact('responseData'));
        } catch (Exception $e) {
            Log::error('Cannot get ZNS quota: ' . $e->getMessage());
            return ApiResponse::error('Cannot get ZNS quota', 500);
        }
    }


    protected function getAccessToken()
    {
        $oa = ZaloOa::where('is_active', 1)->first();

        if (!$oa) {
            Log::error('Không tìm thấy OA nào có trạng thái is_active = 1');
            throw new Exception('Không tìm thấy OA nào có trạng thái is_active = 1');
        }

        $accessToken = Cache::get('access_token');
        $expiration = Cache::get('access_token_expiration');

        if (!$accessToken || now()->greaterThan($expiration)) {
            Log::info('Access token is expired or not available, refreshing token.');

            $refreshToken = $oa->refresh_token;
            $secretKey = env('ZALO_APP_SECRET');
            $appId = env('ZALO_APP_ID');
            $accessToken = $this->refreshAccessToken($refreshToken, $secretKey, $appId);

            // Cập nhật cache với access token mới và thời gian hết hạn
            Cache::put('access_token', $accessToken, 86400);
            Cache::put('access_token_expiration', now()->addHours(24), 86400);
        }

        Log::info('Retrieved access token: ' . $accessToken);
        return $accessToken;
    }

    protected function refreshAccessToken($refreshToken, $secretKey, $appId)
    {
        $client = new Client();
        try {
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
            Log::info("Refresh token response: ", $body);

            if (isset($body['access_token'])) {
                // Lưu access token vào cache và đặt thời gian hết hạn là 24h
                Cache::put('access_token', $body['access_token'], 86400);
                Cache::put('access_token_expiration', now()->addHours(24), 86400);

                if (isset($body['refresh_token'])) {
                    Cache::put('refresh_token', $body['refresh_token'], 7776000); // 90 ngày
                }

                return $body['access_token'];
            } else {
                throw new Exception('Failed to refresh access token');
            }
        } catch (Exception $e) {
            Log::error('Failed to refresh access token: ' . $e->getMessage());
            throw new Exception('Failed to refresh access token');
        }
    }

    public function templateIndex()
    {
        $templates = $this->oaTemplateService->getAllTemplateByOaID();
        $initialTemplateData = null;

        if ($templates->isNotEmpty()) {
            $initialTemplateData = $this->oaTemplateService->getTemplateById($templates->first()->template_id, $templates->first()->oa_id);
        }

        return view('superadmin.message.template', compact('templates', 'initialTemplateData'));
    }

    public function getTemplateDetail(Request $request)
    {
        $templateId = $request->input('template_id');
        $accessToken = $this->getAccessToken();

        try {
            $client = new Client();
            $response = $client->get('https://business.openapi.zalo.me/template/info', [
                'headers' => [
                    'access_token' => $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'template_id' => $templateId,
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true)['data'];

            // Format response for display
            return view('superadmin.message.template_detail', compact('responseData'));
        } catch (Exception $e) {
            Log::error('Failed to get template details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get template details'], 500);
        }
    }


    public function refreshTemplates()
    {
        try {
            $statusMessage = $this->oaTemplateService->checkTemplate();
            $templates = $this->oaTemplateService->getAllTemplateByOaID();

            // Generate HTML for dropdown
            $options = '';
            foreach ($templates as $template) {
                $options .= '<option value="' . $template->template_id . '">' . $template->template_name . '</option>';
            }

            // Get the details of the first template if it exists
            $initialTemplateData = null;
            if ($templates->isNotEmpty()) {
                $initialTemplateData = $this->oaTemplateService->getTemplateById($templates->first()->template_id, $templates->first()->oa_id);
            }

            return response()->json(['templates' => $options, 'initialTemplateData' => $initialTemplateData]);
        } catch (Exception $e) {
            Log::error('Failed to refresh templates: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to refresh templates'], 500);
        }
    }

    // public function test()
    // {
    //     try {
    //         $accessToken = ZaloOa::where('is_active', 1)->first()->access_token;
    //         $client = new Client();
    //         $response = $client->get('https://business.openapi.zalo.me/template/sample-data', [
    //             'headers' => [
    //                 'access_token' => $accessToken,
    //                 'Content-Type' => 'appliaction/json',
    //             ],
    //             'query' => [
    //                 'template_id' => '355423'
    //             ]
    //         ]);
    //         $responseBody = $response->getBody()->getContents();
    //         $responseData = json_decode($responseBody, true)['data'];
    //         return $responseData;
    //     } catch (Exception $e) {
    //         Log::error('Failed to test: ' . $e->getMessage());
    //     }
    // }
}
