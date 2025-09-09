<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Config;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConfigService
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getConfig($id)
    {
        try {
            Log::info('Fetching all configuration');
            return $this->config->where('user_id', $id)->first();
        } catch (Exception $e) {
            Log::error('Failed to fetch configuration: ' . $e->getMessage());
            throw new Exception('Failed to fetch configuration');
        }
    }
    public function updateConfig(int $id, array $data): Config
    {
        try {
            DB::beginTransaction();

            $config = Config::firstOrCreate(['user_id' => $id]);

            // Cập nhật giá trị cho cột receiver
            $config->receiver = $data['receiver'];

            // Thiết lập user_id
            $config->user_id = $id;
            $user = $config->user;

            // Update user details
            $user->store_name = $data['store_name'];
            $user->phone = $data['phone'];
            $user->email = $data['email'];
            $user->address =$data['address'];
            $user->company_name = $data['company_name'];
            $user->save();

            // Thiết lập các cột khác
            $config->bank_account = $data['bank_account'];
            $config->bank_id = $data['bank'];

            if (!empty($data['bank_account']) && !empty($data['bank'])) {
                $bank = Bank::find($data['bank'])->code;
                $bank_account = $data['bank_account'];
                $config->qr = "https://img.vietqr.io/image/{$bank}-{$bank_account}-compact.jpg";
            }

            if (isset($data['logo'])) {
                $logo = $data['logo'];
                $logoFileName = 'image_' . $logo->getClientOriginalName();
                $logoFilePath = 'storage/config/' . $logoFileName;
                Storage::putFileAs('public/config', $logo, $logoFileName);
                $config->logo = $logoFilePath;
            }

            $config->save();

            DB::commit();

            return $config;
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to update configuration: ' . $e->getMessage());
            throw new Exception('Failed to update configuration');
        }
    }
}
