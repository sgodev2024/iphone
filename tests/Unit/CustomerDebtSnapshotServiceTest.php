<?php

namespace Tests\Unit;

use App\Support\DecimalAmount;
use PHPUnit\Framework\TestCase;

class CustomerDebtSnapshotServiceTest extends TestCase
{
    public function test_opening_normalization_preserves_debit_credit_and_zero_nature(): void
    {
        $this->assertSame(
            ['debit' => '6000000.00', 'credit' => '0.00'],
            DecimalAmount::splitNet(DecimalAmount::subtract('10000000.00', '4000000.00'))
        );
        $this->assertSame(
            ['debit' => '0.00', 'credit' => '2000000.00'],
            DecimalAmount::splitNet(DecimalAmount::subtract('0.00', '2000000.00'))
        );
        $this->assertSame(
            ['debit' => '0.00', 'credit' => '0.00'],
            DecimalAmount::splitNet(DecimalAmount::subtract('100.00', '100.00'))
        );
    }
}
