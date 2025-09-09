<?php

namespace App\Services;

use App\Models\OaTemplate;
use App\Models\ZaloOa;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OaTemplateService
{
    protected $oaTemplate;
    protected $zaloOa;
    protected $client;

    public function __construct(OaTemplate $oaTemplate, ZaloOa $zaloOa)
    {
        $this->oaTemplate = $oaTemplate;
        $this->zaloOa = $zaloOa;
        $this->client = new Client(); // Create a single instance of the client
    }

    public function getAllTemplateByOaID()
    {
        try {
            $oa_id = ZaloOa::where('is_active', 1)->first()->id;
            return $this->oaTemplate->where('oa_id', $oa_id)->get();
        } catch (Exception $e) {
            Log::error('Failed to get templates: ' . $e->getMessage());
            throw new Exception('Failed to get templates');
        }
    }

    public function checkTemplate()
    {
        DB::beginTransaction(); // Start transaction

        try {
            $zaloOa = $this->zaloOa->where('is_active', 1)->first();

            if (!$zaloOa) {
                Log::warning('No active Zalo OA found');
                return 'No active Zalo OA found';
            }

            $accessToken = $zaloOa->access_token;
            Log::info('Access Token: ' . $accessToken); // Log the access token

            $response = $this->client->get('https://business.openapi.zalo.me/template/all', [
                'headers' => [
                    'access_token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'offset' => 0,
                    'limit' => 100,
                    // 'status' => 2,
                ],
                'timeout' => 30,
            ]);

            $responseBody = $response->getBody()->getContents();
            Log::info('API Response: ' . $responseBody); // Log API response

            $responseData = json_decode($responseBody, true);

            // Ensure responseData is an array and has the 'data' key
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                $templates = $responseData['data'];
                foreach ($templates as $template) {
                    if (isset($template['templateId']) && isset($template['templateName'])) {
                        $existingTemplate = $this->oaTemplate
                            ->where('template_id', $template['templateId'])
                            ->where('oa_id', $zaloOa->id)
                            ->first();

                        // Nếu template chưa tồn tại, tạo mới
                        if (!$existingTemplate) {
                            $this->oaTemplate->create([
                                'oa_id' => $zaloOa->id,
                                'template_id' => $template['templateId'],
                                'template_name' => $template['templateName']
                            ]);
                        }

                        // Lấy thông tin chi tiết cho từng template và cập nhật giá
                        $templateDetailResponse = $this->client->get('https://business.openapi.zalo.me/template/info', [
                            'headers' => [
                                'access_token' => $accessToken,
                                'Content-Type' => 'application/json'
                            ],
                            'query' => [
                                'template_id' => $template['templateId'],
                            ],
                            'timeout' => 30,
                        ]);

                        $templateDetailBody = $templateDetailResponse->getBody()->getContents();
                        Log::info('Template Detail API Response: ' . $templateDetailBody); // Log template detail API response

                        $templateDetailData = json_decode($templateDetailBody, true)['data'];

                        // Cập nhật giá trong cơ sở dữ liệu
                        $this->oaTemplate->updateOrCreate(
                            ['template_id' => $template['templateId'], 'oa_id' => $zaloOa->id],
                            ['price' => $templateDetailData['price'] ?? null] // Giả sử `price` nằm trong `templateDetailData`
                        );
                    } else {
                        Log::warning('Template missing required fields: ' . print_r($template, true));
                    }
                }
                DB::commit(); // Commit transaction
                return 'Templates processed successfully';
            } else {
                Log::error('Invalid response structure from Zalo API');
                DB::rollBack(); // Roll back transaction
                return 'Invalid response structure from Zalo API';
            }
        } catch (Exception $e) {
            DB::rollBack(); // Roll back transaction in case of exception
            Log::error('Failed to process template: ' . $e->getMessage());
            return 'Failed to process template: ' . $e->getMessage();
        }
    }
    public function getTemplateById($template_id, $oa_id)
    {
        $accessToken = $this->zaloOa->where('id', $oa_id)->first()->access_token;
        try {
            $response = $this->client->get('https://business.openapi.zalo.me/template/info', [
                'headers' => [
                    'access_token' => $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'template_id' => $template_id,
                ],
            ]);
            $responseBody = $response->getBody()->getContents();
            Log::info('API Response: ' . $responseBody);
            $responseData = json_decode($responseBody, true)['data'];
            return $responseData;
        } catch (Exception $e) {
            Log::error('Failed to find template: ' . $e->getMessage());
            throw new Exception("Failed to find template: " . $e->getMessage());
        }
    }
}
