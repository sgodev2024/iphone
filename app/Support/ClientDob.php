<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;

class ClientDob implements ValidationRule
{
    public static function minDate(): string
    {
        return CarbonImmutable::today()->subYearsNoOverflow(120)->format('Y-m-d');
    }

    public static function maxDate(): string
    {
        return CarbonImmutable::today()->subYearsNoOverflow(10)->format('Y-m-d');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $date = is_string($value)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $value)
            : false;

        if ($date === false || $date->format('Y-m-d') !== $value) {
            $fail('Ngày sinh không đúng định dạng.');

            return;
        }

        if ($value < self::minDate()) {
            $fail('Ngày sinh không hợp lệ. Tuổi khách hàng không được vượt quá 120.');
        } elseif ($value > self::maxDate()) {
            $fail('Khách hàng phải từ đủ 10 tuổi trở lên.');
        }
    }
}
