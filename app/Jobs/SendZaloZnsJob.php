<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\OaTemplate;
use App\Models\ZaloOa;
use App\Models\ZnsMessage;
use App\Services\ZaloOaService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendZaloZnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;

    /**
     * Create a new job instance.
     *
     * @param Campaign $campaign
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $oa_id = ZaloOa::where('is_active', 1)->first()->id;
            $templateId = $this->campaign->template_id;
            $template = OaTemplate::where('id', $templateId)->first()->template_id;

            $payload = [
                'phone' => preg_replace('/^0/', '84', $this->campaign->user->phone),
                'template_id' => $template,
                'template_data' => [
                    'date' => Carbon::now()->format('d/m/Y'),
                    'name' => $this->campaign->user->name,
                    'order_code' => $this->campaign->user->id,
                    'phone_number' => $this->campaign->user->phone,
                    'status' => '',
                ],
            ];

            $client = new Client();
            $zaloOaService = app(ZaloOaService::class); // Create an instance of ZaloOaService
            $accessToken = $zaloOaService->getAccessToken();

            try {
                $response = $client->post('https://business.openapi.zalo.me/message/template', [
                    'headers' => [
                        'access_token' => $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]);

                $responseBody = $response->getBody()->getContents();
                Log::info("Api Response: " . $responseBody);

                $responseData = json_decode($responseBody, true);
                $status = $responseData['error'] == 0 ? 1 : 0;

                ZnsMessage::create([
                    'name' => $this->campaign->user->name,
                    'phone' => $this->campaign->user->phone,
                    'sent_at' => Carbon::now(),
                    'status' => $status,
                    'note' => $responseData['message'],
                    'template_id' => $templateId,
                    'oa_id' => $oa_id,
                ]);

                if ($status == 1) {
                    Log::info('Gửi Zns Thành công');
                } else {
                    Log::error('Gửi Zns thất bại: ' . $responseBody);
                }
            } catch (Exception $e) {
                Log::error('Lỗi khi gửi tin nhắn: ' . $e->getMessage());

                ZnsMessage::create([
                    'name' => $this->campaign->user->name,
                    'phone' => $this->campaign->user->phone,
                    'sent_at' => Carbon::now(),
                    'status' => 0,
                    'note' => $e->getMessage(),
                    'template_id' => $templateId,
                    'oa_id' => $oa_id,
                ]);
            }
        } catch (Exception $e) {
            Log::error("Failed to send ZNS message: " . $e->getMessage());
            throw new Exception("Failed to send ZNS message");
        }
    }
}
