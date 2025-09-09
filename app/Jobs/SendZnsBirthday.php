<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\OaTemplate;
use App\Models\User;
use App\Models\ZaloOa;
use App\Models\ZnsMessage;
use App\Services\Admins\ZaloOaService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendZnsBirthday implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $campaignId;
    protected $zaloOaService;

    /**
     * Tạo một đối tượng công việc mới.
     *
     * @param \App\Models\User $user
     * @param int $campaignId
     */
    public function __construct(User $user, $campaignId, ZaloOaService $zaloOaService)
    {
        $this->user = $user;
        $this->campaignId = $campaignId;
        $this->zaloOaService = $zaloOaService;
    }

    /**
     * Thực thi công việc.
     */
    public function handle()
    {
        try {
            // Kiểm tra ngày sinh nhật của người dùng
            $dob = Carbon::parse($this->user->dob);
            $today = Carbon::now();

            $birthdayThisYear = Carbon::createFromDate($today->year, $dob->month, $dob->day);

            // Nếu ngày sinh nhật đã qua thì cộng thêm 1 năm
            if ($birthdayThisYear->isPast()) {
                $birthdayThisYear->addYear();
            }

            // Đặt thời gian gửi tin nhắn
            $sendAt = $birthdayThisYear->hour(9)->minute(30);

            // Nếu chưa đến thời gian gửi tin nhắn
            if (now()->lessThan($sendAt)) {
                Log::info("Chưa đến thời gian gửi tin nhắn sinh nhật cho người dùng ID: {$this->user->id}");
                return;
            }

            // Lấy thông tin chiến dịch
            $campaign = Campaign::find($this->campaignId);

            // Kiểm tra sự tồn tại của chiến dịch
            if (!$campaign) {
                Log::error("Chiến dịch ID: {$this->campaignId} không tồn tại.");
                return;
            }

            // Kiểm tra trạng thái chiến dịch
            if ($campaign->status != 1) {
                Log::info("Chiến dịch ID: {$this->campaignId} hiện không hoạt động.");
                return;
            }

            // Kiểm tra sự tồn tại của template_id
            $templateId = $campaign->template_id;
            if (!$templateId) {
                Log::error("Template ID không tồn tại cho chiến dịch ID: {$this->campaignId}.");
                return;
            }
            $template = OaTemplate::where('id', $templateId)->first()->template_id;
            // Chuẩn bị dữ liệu để gửi tin nhắn Zalo
            $payload = [
                'phone' => preg_replace('/^0/', '84', $this->user->phone),
                'template_id' => $template,
                'template_data' => [
                    'date' => Carbon::now()->format('d/m/Y'),
                    'name' => $this->user->name,
                    'order_code' => $this->user->id,
                    'phone_number' => $this->user->phone,
                    'status' => 'Đăng ký thành công',
                ],
            ];
            $oa_id = ZaloOa::where('is_active', 1)->first()->id;
            // Tạo client GuzzleHttp
            $client = new Client();
            $accessToken = $this->zaloOaService->getAccessToken(); // Thay thế bằng access token thực tế

            $response = $client->post('https://business.openapi.zalo.me/message/template', [
                'headers' => [
                    'access_token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            // Lấy nội dung phản hồi từ API
            $responseBody = $response->getBody()->getContents();
            Log::info("Phản hồi từ API Zalo: " . $responseBody);

            // Phân tích phản hồi từ API
            $responseData = json_decode($responseBody, true);
            $status = $responseData['error'] == 0 ? 1 : 0;

            // Lưu thông tin tin nhắn vào cơ sở dữ liệu
            ZnsMessage::create([
                'name' => $this->user->name,
                'phone' => $this->user->phone,
                'sent_at' => Carbon::now(),
                'status' => $status,
                'note' => $responseData['message'],
                'template_id' => $templateId,
                'oa_id' => $oa_id,
            ]);

            // Kiểm tra trạng thái gửi tin nhắn
            if ($status == 1) {
                Log::info("Gửi ZNS thành công cho người dùng ID: {$this->user->id}");
            } else {
                Log::error("Gửi ZNS thất bại cho người dùng ID: {$this->user->id}. Lỗi: " . $responseData['message']);
            }
        } catch (Exception $e) {
            Log::error("Lỗi khi gửi tin nhắn cho người dùng ID: {$this->user->id}. Lỗi: " . $e->getMessage());

            // Lưu thông tin lỗi vào cơ sở dữ liệu
            ZnsMessage::create([
                'name' => $this->user->name,
                'phone' => $this->user->phone,
                'sent_at' => Carbon::now(),
                'status' => 0,
                'note' => $e->getMessage(),
                'template_id' => $templateId ?? null,
                'oa_id' => $this->user->storage_id,
            ]);
        }
    }
}
