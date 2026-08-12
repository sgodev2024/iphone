<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DecimalAmount;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SupplierDebtReportService
{
    public function report(User $actor, string $fromDate, string $toDate, string $nameFilter = ''): Collection
    {
        $ownerId = (int) $actor->ownerId();
        $account331 = $this->resolvePayableAccount();
        $nameFilter = trim($nameFilter);

        $ledgerRows = DB::table('companies as c')
            ->leftJoin('transaction_entries as te', function (JoinClause $join) use ($account331): void {
                $join->on('te.tableable_id', '=', 'c.id')
                    ->where('te.tableable_type', Company::class)
                    ->where('te.account_id', $account331->id);
            })
            ->leftJoin('transactions as t', function (JoinClause $join) use ($ownerId): void {
                $join->on('t.id', '=', 'te.transaction_id')
                    ->where('t.user_id', $ownerId)
                    ->where('t.status', Transaction::STATUS_COMPLETED);
            })
            ->where('c.user_id', $ownerId)
            ->when($nameFilter !== '', function ($query) use ($nameFilter): void {
                $query->where('c.name', 'like', '%'.$nameFilter.'%');
            })
            ->select([
                'c.id as company_id',
                'c.name as company_name',
                'c.phone as company_phone',
                't.transaction_date',
                'te.debit_amount',
                'te.credit_amount',
            ])
            ->orderBy('c.id')
            ->get();

        $companyBalances = [];

        foreach ($ledgerRows as $row) {
            $companyId = (int) $row->company_id;
            $companyBalances[$companyId] ??= [
                'company_id' => $companyId,
                'company_name' => (string) $row->company_name,
                'company_phone' => $row->company_phone,
                'opening_debit_raw' => '0.00',
                'opening_credit_raw' => '0.00',
                'period_debit_raw' => '0.00',
                'period_credit_raw' => '0.00',
            ];

            if ($row->transaction_date === null) {
                continue;
            }

            $debit = DecimalAmount::normalize((string) $row->debit_amount);
            $credit = DecimalAmount::normalize((string) $row->credit_amount);

            if ((string) $row->transaction_date < $fromDate) {
                $companyBalances[$companyId]['opening_debit_raw'] = DecimalAmount::add(
                    $companyBalances[$companyId]['opening_debit_raw'],
                    $debit
                );
                $companyBalances[$companyId]['opening_credit_raw'] = DecimalAmount::add(
                    $companyBalances[$companyId]['opening_credit_raw'],
                    $credit
                );
            } elseif ((string) $row->transaction_date <= $toDate) {
                $companyBalances[$companyId]['period_debit_raw'] = DecimalAmount::add(
                    $companyBalances[$companyId]['period_debit_raw'],
                    $debit
                );
                $companyBalances[$companyId]['period_credit_raw'] = DecimalAmount::add(
                    $companyBalances[$companyId]['period_credit_raw'],
                    $credit
                );
            }
        }

        return collect($companyBalances)
            ->map(fn (array $row): object => $this->normalizeRow((object) $row))
            ->filter(fn (object $row): bool => $this->hasBalanceActivity($row))
            ->values();
    }

    private function resolvePayableAccount(): Account
    {
        $account = Account::query()
            ->where('code', '331')
            ->where('status', true)
            ->first();

        if (! $account) {
            throw new RuntimeException('Canonical payable account 331 is missing or inactive.');
        }

        return $account;
    }

    private function normalizeRow(object $row): object
    {
        $openingDebitRaw = DecimalAmount::normalize((string) $row->opening_debit_raw);
        $openingCreditRaw = DecimalAmount::normalize((string) $row->opening_credit_raw);
        $periodDebit = DecimalAmount::normalize((string) $row->period_debit_raw);
        $periodCredit = DecimalAmount::normalize((string) $row->period_credit_raw);
        $openingNet = DecimalAmount::subtract($openingCreditRaw, $openingDebitRaw);
        $endingNet = DecimalAmount::add($openingNet, $periodCredit, '-'.$periodDebit);

        [$openingDebit, $openingCredit] = $this->splitPayableNet($openingNet);
        [$endingDebit, $endingCredit] = $this->splitPayableNet($endingNet);

        return (object) [
            'company_id' => (int) $row->company_id,
            'company_name' => (string) $row->company_name,
            'company_phone' => $row->company_phone,
            'opening_debit' => $openingDebit,
            'opening_credit' => $openingCredit,
            'period_debit' => $periodDebit,
            'period_credit' => $periodCredit,
            'ending_debit' => $endingDebit,
            'ending_credit' => $endingCredit,
        ];
    }

    private function splitPayableNet(string $net): array
    {
        $net = DecimalAmount::normalize($net);

        if (DecimalAmount::compare($net, '0.00') > 0) {
            return ['0.00', $net];
        }

        if (DecimalAmount::compare($net, '0.00') < 0) {
            return [DecimalAmount::absolute($net), '0.00'];
        }

        return ['0.00', '0.00'];
    }

    private function hasBalanceActivity(object $row): bool
    {
        return ! DecimalAmount::isZero($row->opening_debit)
            || ! DecimalAmount::isZero($row->opening_credit)
            || ! DecimalAmount::isZero($row->period_debit)
            || ! DecimalAmount::isZero($row->period_credit);
    }
}
