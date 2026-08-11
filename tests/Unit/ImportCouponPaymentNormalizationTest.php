<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ImportCouponController;
use App\Models\ImportCoupon;
use ReflectionClass;
use Tests\TestCase;

class ImportCouponPaymentNormalizationTest extends TestCase
{
    /**
     * @dataProvider paymentCases
     */
    public function test_import_coupon_payment_is_normalized_by_business_rule(
        string $paymentMethod,
        int $inputPaidAmount,
        int $total,
        array $expected
    ): void {
        $payment = $this->normalizePayment($paymentMethod, $inputPaidAmount, $total);

        $this->assertSame($expected, $payment);
    }

    public static function paymentCases(): array
    {
        return [
            'cash pays full amount' => [
                ImportCoupon::PAYMENT_METHOD_CASH,
                0,
                100000,
                [
                    'payment_method' => ImportCoupon::PAYMENT_METHOD_CASH,
                    'paid_amount' => 100000,
                    'debt_amount' => 0,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_PAID,
                ],
            ],
            'bank transfer pays full amount' => [
                ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER,
                0,
                100000,
                [
                    'payment_method' => ImportCoupon::PAYMENT_METHOD_BANK_TRANSFER,
                    'paid_amount' => 100000,
                    'debt_amount' => 0,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_PAID,
                ],
            ],
            'supplier debt without upfront payment' => [
                ImportCoupon::PAYMENT_METHOD_DEBT,
                0,
                100000,
                [
                    'payment_method' => ImportCoupon::PAYMENT_METHOD_DEBT,
                    'paid_amount' => 0,
                    'debt_amount' => 100000,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_UNPAID,
                ],
            ],
            'supplier debt with partial payment' => [
                ImportCoupon::PAYMENT_METHOD_DEBT,
                40000,
                100000,
                [
                    'payment_method' => ImportCoupon::PAYMENT_METHOD_DEBT,
                    'paid_amount' => 40000,
                    'debt_amount' => 60000,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_PARTIAL,
                ],
            ],
        ];
    }

    private function normalizePayment(string $paymentMethod, int $paidAmount, int $total): array
    {
        $reflection = new ReflectionClass(ImportCouponController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('normalizePaymentData');
        $method->setAccessible(true);

        return $method->invoke($controller, $paymentMethod, $paidAmount, $total);
    }
}
