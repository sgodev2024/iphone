<?php

namespace App\Services;

use App\Jobs\SendZaloZnsJob;
use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\City;
use App\Models\User;
use App\Services\ZaloOaService;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CampaignService
{
    protected $campaign;
    protected $campaignDetail;

    public function __construct(Campaign $campaign, CampaignDetail $campaignDetail)
    {
        $this->campaign = $campaign;
        $this->campaignDetail = $campaignDetail;
    }

    public function getAllCampaign()
    {
        try {
            return $this->campaign->get();
        } catch (Exception $e) {
            Log::error('Failed to fetch all campaign: ' . $e->getMessage());
            throw new Exception('Failed to fetch all campaign');
        }
    }

    public function getPaginateCampaign()
    {
        try {
            return $this->campaign->orderByDesc('created_at')->paginate(10);
        } catch (Exception $e) {
            Log::error('Failed to fetch paginated campaign: ' . $e->getMessage());
            throw new Exception('Failed to fetch paginated campaign');
        }
    }

    public function createNewCampaign(array $data)
    {
        DB::beginTransaction();
        try {
            // Tạo bản ghi chiến dịch mới
            $campaign = $this->campaign->create([
                'name' => $data['name'],
                'template_id' => $data['template_id'],
                'sent_time' => $data['sent_time'],
                'sent_date' => $data['sent_date'],
                'status' => 1
            ]);

            // Xử lý file Excel nếu có
            if (isset($data['import_file']) && $data['import_file']->isValid()) {
                $filePath = $data['import_file']->getRealPath();
                $spreadsheet = IOFactory::load($filePath); // Sử dụng IOFactory để đọc file Excel
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                foreach (array_slice($rows, 1) as $row) {
                    if (isset($row[0]) && !empty($row[0])) {
                        $existingUser = User::where('phone', $row[1])->first();
                        $city = City::where('name', $row[4])->first();

                        if ($existingUser) {
                            // Nếu người dùng đã tồn tại, tạo chi tiết chiến dịch
                            CampaignDetail::create([
                                'campaign_id' => $campaign->id,
                                'user_id' => $existingUser->id,
                                'data' => json_encode([]),
                            ]);
                        } else {
                            // Tạo người dùng mới nếu chưa tồn tại
                            $password = '123456';
                            $hashedPassword = Hash::make($password);

                            try {
                                $dob = Carbon::createFromFormat('d/m/Y', $row[3])->format('Y-m-d');
                            } catch (\Exception $e) {
                                $dob = null; // Xử lý ngày sinh không hợp lệ
                            }

                            $newUser = User::create([
                                'name' => $row[0],
                                'phone' => $row[1],
                                'email' => $row[2],
                                'password' => $hashedPassword,
                                'dob' => $dob,
                                'status' => 'active',
                                'role_id' => 1,
                                'city_id' => $city->id ?? null,
                                'address' => $row[5],
                            ]);

                            // Tạo chi tiết chiến dịch cho người dùng mới
                            CampaignDetail::create([
                                'campaign_id' => $campaign->id,
                                'user_id' => $newUser->id,
                                'data' => json_encode([]),
                            ]);
                        }
                    }
                }
            }

            // Lưu thay đổi vào database
            DB::commit();
            return $campaign;
        } catch (\Exception $e) {
            // Rollback nếu có lỗi xảy ra
            DB::rollBack();
            Log::error('Failed to create new campaign: ' . $e->getMessage());
            throw new \Exception("Failed to create new campaign");
        }
    }
    public function updateCampaign(array $data, $id)
    {
        DB::beginTransaction();
        try {
            // Tìm chiến dịch cần cập nhật
            $campaign = $this->campaign->find($id);
            if (!$campaign) {
                throw new Exception('Campaign not found!');
            }

            // Cập nhật thông tin chiến dịch
            $campaign->update([
                'name' => $data['name'],
                'template_id' => $data['template_id'],
                'delay_date' => $data['delay_date'],
            ]);

            // Xóa các chi tiết chiến dịch được chỉ định
            if (isset($data['user_ids_to_remove'])) {
                $this->campaignDetail->where('campaign_id', $id)
                    ->whereIn('user_id', $data['user_ids_to_remove'])
                    ->delete();
            }

            // Thêm mới các chi tiết chiến dịch
            if (isset($data['user_ids_to_add'])) {
                foreach ($data['user_ids_to_add'] as $user_id) {
                    // Kiểm tra nếu user_id đã tồn tại trong chiến dịch
                    $existingDetail = $this->campaignDetail->where([
                        ['campaign_id', $id],
                        ['user_id', $user_id]
                    ])->first();

                    if (!$existingDetail) {
                        // Nếu chi tiết không tồn tại, tạo mới
                        $this->campaignDetail->create([
                            'campaign_id' => $campaign->id,
                            'user_id' => $user_id,
                            'scheduled_date' => now()->addDays($data['delay_days']),
                            'data' => json_encode([]),
                        ]);
                    }
                }
            }

            DB::commit();
            return $campaign;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update campaign: ' . $e->getMessage());
            throw new Exception("Failed to update campaign");
        }
    }

    public function deleteCampaign($id)
    {
        DB::beginTransaction();
        try {
            $campaign = $this->campaign->find($id);
            $campaignDetail = $this->campaignDetail->where('campaign_id', $id)->delete();
            $campaign->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete campaign: ' . $e->getMessage());
            throw new Exception('Failed to delete campaign');
        }
    }
}
