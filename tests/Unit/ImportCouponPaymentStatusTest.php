<?php

namespace Tests\Unit;

use App\Models\ImportCoupon;
use Tests\TestCase;

class ImportCouponPaymentStatusTest extends TestCase
{
    /**
     * @dataProvider paymentStatusCases
     */
    public function test_payment_status_is_resolved_safely_for_legacy_null_data(
        array $attributes,
        string $expectedStatus,
        string $expectedLabel,
        string $expectedBadgeClass
    ): void {
        $coupon = new ImportCoupon;
        $coupon->setRawAttributes($attributes);

        $this->assertSame($expectedStatus, $coupon->resolved_payment_status);
        $this->assertSame($expectedLabel, $coupon->payment_status_label);
        $this->assertSame($expectedBadgeClass, $coupon->payment_status_badge_class);
    }

    public static function paymentStatusCases(): array
    {
        return [
            'paid' => [
                [
                    'total' => 100000,
                    'paid_amount' => 100000,
                    'debt_amount' => 0,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_PAID,
                ],
                ImportCoupon::PAYMENT_STATUS_PAID,
                'Đã hoàn thành',
                'badge-success',
            ],
            'partial' => [
                [
                    'total' => 100000,
                    'paid_amount' => 40000,
                    'debt_amount' => 60000,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_PARTIAL,
                ],
                ImportCoupon::PAYMENT_STATUS_PARTIAL,
                'Thanh toán một phần',
                'badge-warning',
            ],
            'unpaid' => [
                [
                    'total' => 100000,
                    'paid_amount' => 0,
                    'debt_amount' => 100000,
                    'payment_status' => ImportCoupon::PAYMENT_STATUS_UNPAID,
                ],
                ImportCoupon::PAYMENT_STATUS_UNPAID,
                'Công nợ',
                'badge-danger',
            ],
            'legacy payment_ncc fallback' => [
                [
                    'total' => 100000,
                    'payment_ncc' => 25000,
                    'paid_amount' => null,
                    'debt_amount' => null,
                    'payment_status' => null,
                ],
                ImportCoupon::PAYMENT_STATUS_PARTIAL,
                'Thanh toán một phần',
                'badge-warning',
            ],
            'legacy paid fallback' => [
                [
                    'total' => 100000,
                    'paid_amount' => 100000,
                    'debt_amount' => null,
                    'payment_status' => null,
                ],
                ImportCoupon::PAYMENT_STATUS_PAID,
                'Đã hoàn thành',
                'badge-success',
            ],
            'legacy unpaid fallback' => [
                [
                    'total' => 100000,
                    'paid_amount' => null,
                    'payment_ncc' => null,
                    'debt_amount' => 100000,
                    'payment_status' => null,
                ],
                ImportCoupon::PAYMENT_STATUS_UNPAID,
                'Công nợ',
                'badge-danger',
            ],
        ];
    }
}
