<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Config;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->upsertAccount('111', 'Tiền mặt', null, 1, true);
        $bankParentAccount = $this->upsertAccount('112', 'Tiền gửi ngân hàng', null, 1, true);
        $this->upsertAccount('131', 'Phải thu khách hàng', null, 1, true);
        $this->upsertAccount('156', 'Hàng hóa', null, 1, true);
        $this->upsertAccount('331', 'Phải trả nhà cung cấp', null, 1, true);

        $this->upsertConfiguredBankAccount($bankParentAccount);
    }

    private function upsertConfiguredBankAccount(Account $bankParentAccount): void
    {
        $config = Config::query()
            ->with('bank')
            ->whereNotNull('bank_id')
            ->first();

        if (! $config || ! $config->bank) {
            return;
        }

        $bankCode = preg_replace('/[^A-Za-z0-9]/', '', $config->bank->code ?: $config->bank->shortName);
        $bankCode = strtoupper($bankCode ?: 'BANK');
        $accountCode = '112' . $bankCode;
        $bankLabel = $config->bank->shortName ?: $config->bank->name;
        $accountNumber = $config->bank_account ? " - {$config->bank_account}" : '';

        $this->upsertAccount(
            $accountCode,
            "Tiền gửi ngân hàng {$bankLabel}{$accountNumber}",
            (int) $bankParentAccount->id,
            2,
            false
        );
    }

    private function upsertAccount(
        string $code,
        string $name,
        ?int $parentId,
        int $level,
        bool $isDefault
    ): Account {
        return Account::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'parent_id' => $parentId,
                'level' => $level,
                'status' => true,
                'is_default' => $isDefault,
            ]
        );
    }
}
