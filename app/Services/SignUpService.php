<?php

namespace App\Services;

use App\Jobs\SendZnsBirthday;
use App\Jobs\SendZnsReminderJob;
use App\Mail\SuperAdmin as MailSuperAdmin;
use App\Mail\UserRegistered;
use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\City;
use App\Models\Config;
use App\Models\Field;
use App\Models\Message;
use App\Models\OaTemplate;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\ZaloOa;
use App\Models\ZnsMessage;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SignUpService
{
    protected $user;
    protected $city;
    protected $field;
    protected $superAdmin;
    public function __construct(User $user, City $city, Field $field, SuperAdmin $superAdmin)
    {
        $this->user = $user;
        $this->city = $city;
        $this->field = $field;
        $this->superAdmin = $superAdmin;
    }

    public function signup(array $data)
    {
        DB::beginTransaction();
        try {
            Log::info("Creating new account: {$data['name']}");
            $password = $this->RenderRandomPassword();
            $hashedPassword = Hash::make($password);

            // Tạo người dùng mới
            $user = $this->user->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'company_name' => $data['company_name'],
                'dob' => $data['dob'],
                'password' => $hashedPassword,
                'status' => 'active',
                'role_id' => 1,
                'city_id' => $data['city'],
                'tax_code' => $data['tax_code'],
                'store_name' => $data['store_name'],
                'field_id' => $data['field'],
                'domain' => $data['store_domain'],
                'address' => $data['address'],
            ]);

            // Tạo cấu hình cho người dùng
            $config = new Config();
            $config->user_id = $user->id;
            $config->save();

            // $campaignId = Campaign::where('name', 'Nhắc nhở sau đăng ký')->first()->id;
            // $campaignDetail = new CampaignDetail();
            // $campaignDetail->user_id = $user->id;
            // $campaignDetail->campaign_id = $campaignId;
            // $campaignDetail->save();

            // $campaignId2 = Campaign::where('name', 'Sự kiện sinh nhật')->first()->id;
            // $campaginDetail2 = new CampaignDetail();
            // $campaginDetail2->user_id = $user->id;
            // $campaginDetail2->campaign_id = $campaignId2;
            // $campaginDetail2->save();
            // // Gửi email thông báo cho quản trị viên và người dùng
            // $superadmin = $this->superAdmin->first();
            // Mail::to($superadmin)->send(new MailSuperAdmin($user, $password));
            // Mail::to($data['email'])->send(new UserRegistered($user, $password));

            // $sendReminderAt = Carbon::now()->addWeek()->startOfDay()->addHours(9)->addMinutes(30);
            // SendZnsReminderJob::dispatch($user, $campaignId)->delay($sendReminderAt);

            // $dob = Carbon::parse($data['dob']);
            // $today = Carbon::now();
            // $birthdayThisYear = Carbon::createFromDate($today->year, $dob->month, $dob->day);
            // if ($birthdayThisYear->isPast()) {
            //     $birthdayThisYear->addYear();
            // }

            // $sendBirthdayAt = $birthdayThisYear->hour(9)->minute(30);
            // SendZnsBirthday::dispatch($user, $campaignId2)->delay($sendBirthdayAt);
            // Lấy access token hợp lệ
            // $accessToken = $this->getAccessToken();
            // $oa_id = ZaloOa::where('is_active', 1)->first()->id;

            // try {
            //     // Gửi yêu cầu tới API Zalo
            //     $client = new Client();
            //     $response = $client->post('https://business.openapi.zalo.me/message/template', [
            //         'headers' => [
            //             'access_token' => $accessToken,
            //             'Content-Type' => 'application/json'
            //         ],
            //         'json' => [
            //             'phone' => preg_replace('/^0/', '84', $data['phone']),
            //             'template_id' => '355330',
            //             'template_data' => [
            //                 'date' => Carbon::now()->format('d/m/Y') ?? "",
            //                 'name' => $data['name'] ?? "",
            //                 'order_code' => $user->id,
            //                 'phone_number' => $data['phone'],
            //                 'status' => 'Đăng ký thành công'
            //             ]
            //         ]
            //     ]);

            //     $responseBody = $response->getBody()->getContents();
            //     Log::info("API Response: " . $responseBody);

            //     $responseData = json_decode($responseBody, true);
            //     $status = $responseData['error'] == 0 ? 1 : 0;

            //     $template_id = OaTemplate::where('template_id', '355330')->first()->id;

            //     // Lưu thông tin tin nhắn vào cơ sở dữ liệu
            //     ZnsMessage::create([
            //         'name' => $data['name'],
            //         'phone' => $data['phone'],
            //         'sent_at' => Carbon::now(),
            //         'status' => $status,
            //         'note' => $responseData['message'],
            //         'template_id' => $template_id,
            //         'oa_id' => $oa_id,
            //     ]);

            //     if ($status == 1) {
            //         Log::info('Gửi ZNS thành công');
            //     } else {
            //         Log::error('Gửi ZNS thất bại: ' . $response->getBody());
            //     }
            // } catch (Exception $e) {
            //     Log::error('Lỗi khi gửi tin nhắn: ' . $e->getMessage());

            //     // Lưu thông tin tin nhắn vào cơ sở dữ liệu khi gặp lỗi
            //     ZnsMessage::create([
            //         'name' => $data['name'],
            //         'phone' => $data['phone'],
            //         'sent_at' => Carbon::now(),
            //         'status' => 0,
            //         'note' => $e->getMessage(),
            //         'oa_id' => $oa_id,
            //     ]);
            // }

            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create new account: ' . $e->getMessage());
            throw new Exception('Failed to create new account');
        }
    }

    protected function getAccessToken()
    {
        $oa = ZaloOa::where('is_active', 1)->first();

        if (!$oa) {
            Log::error('Không tìm thấy OA nào có trạng thái is_active = 1');
            throw new Exception('Không tìm thấy OA nào có trạng thái is_active = 1');
        }

        $accessToken = $oa->access_token;
        $refreshToken = $oa->refresh_token;

        if (!$accessToken || Cache::has('access_token_expired')) {
            $secretKey = env('ZALO_APP_SECRET');
            $appId = env('ZALO_APP_ID');
            $accessToken = $this->refreshAccessToken($refreshToken, $secretKey, $appId);

            $oa->update(['access_token' => $accessToken]);
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

            $body = json_decode($response->getBody(), true);
            Log::info("Refresh token response: " . json_encode($body));

            if (isset($body['access_token'])) {
                // Lưu access token vào cache và đặt thời gian hết hạn là 24h
                Cache::put('access_token', $body['access_token'], 86400);
                Cache::forget('access_token_expired');

                if (isset($body['refresh_token'])) {
                    Cache::put('refresh_token', $body['refresh_token'], 7776000);
                }
                return [$body['access_token'], $body['refresh_token']];
            } else {
                throw new Exception('Failed to refresh access token');
            }
        } catch (Exception $e) {
            Log::error('Failed to refresh access token: ' . $e->getMessage());
            throw new Exception('Failed to refresh access token');
        }
    }
    public function RenderRandomPassword()
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        $allCharacters = $uppercase . $lowercase . $numbers;
        $password = '';

        // Add one random character from each category to ensure the password contains at least one uppercase letter, one lowercase letter, and one number
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];

        // Add remaining characters randomly from the combined set
        for ($i = 3; $i < 8; $i++) {
            $password .= $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        // Shuffle the password to ensure the characters are randomly distributed
        return str_shuffle($password);
    }
    public function getAllCities()
    {
        try {
            return $this->city->all();
        } catch (Exception $e) {
            Log::error('Failed to fetch all cities: ' . $e->getMessage());
            throw new Exception('Failed to fetch all city');
        }
    }
    public function getAllFields()
    {
        try {
            return $this->field->all();
        } catch (Exception $e) {
            Log::error('Failed to fetch all fields: ' . $e->getMessage());
            throw new Exception('Failed to fetch all field');
        }
    }
}
