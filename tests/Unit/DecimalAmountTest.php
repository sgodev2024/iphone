<?php

namespace Tests\Unit;

use App\Support\DecimalAmount;
use PHPUnit\Framework\TestCase;

class DecimalAmountTest extends TestCase
{
    public function test_exact_arithmetic_does_not_use_floating_point(): void
    {
        $this->assertSame('19999999999999.98', DecimalAmount::add('9999999999999.99', '9999999999999.99'));
        $this->assertSame('19999999999999.97', DecimalAmount::subtract('19999999999999.98', '0.01'));
        $this->assertSame(
            ['debit' => '0.00', 'credit' => '19999999999999.97'],
            DecimalAmount::splitNet('-19999999999999.97')
        );
        $this->assertSame('19.999.999.999.999,97', formatExactMoney('19999999999999.97'));
    }
}
