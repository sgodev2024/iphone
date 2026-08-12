<?php

namespace App\Support;

use InvalidArgumentException;

final class DecimalAmount
{
    public static function normalize(string|int $amount): string
    {
        return self::fromCents(self::toCents((string) $amount));
    }

    public static function add(string|int ...$amounts): string
    {
        $total = '0';

        foreach ($amounts as $amount) {
            $total = self::addIntegers($total, self::toCents((string) $amount));
        }

        return self::fromCents($total);
    }

    public static function subtract(string|int $left, string|int $right): string
    {
        return self::fromCents(
            self::addIntegers(self::toCents((string) $left), self::negate(self::toCents((string) $right)))
        );
    }

    public static function compare(string|int $left, string|int $right): int
    {
        return self::compareIntegers(self::toCents((string) $left), self::toCents((string) $right));
    }

    public static function isZero(string|int $amount): bool
    {
        return self::compare($amount, '0.00') === 0;
    }

    public static function splitNet(string|int $net): array
    {
        $normalized = self::normalize($net);

        if (self::compare($normalized, '0.00') > 0) {
            return ['debit' => $normalized, 'credit' => '0.00'];
        }

        if (self::compare($normalized, '0.00') < 0) {
            return ['debit' => '0.00', 'credit' => self::absolute($normalized)];
        }

        return ['debit' => '0.00', 'credit' => '0.00'];
    }

    public static function absolute(string|int $amount): string
    {
        return ltrim(self::normalize($amount), '-');
    }

    private static function toCents(string $amount): string
    {
        $amount = trim($amount);

        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException("Invalid exact decimal amount [{$amount}].");
        }

        $negative = ($matches[1] ?? '') === '-';
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', 2, '0');
        $digits = self::trimAbsolute($whole.$fraction);

        return $negative && $digits !== '0' ? '-'.$digits : $digits;
    }

    private static function fromCents(string $cents): string
    {
        $negative = str_starts_with($cents, '-');
        $digits = self::trimAbsolute(ltrim($cents, '-'));
        $digits = str_pad($digits, 3, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -2);
        $fraction = substr($digits, -2);

        return ($negative && $digits !== '000' ? '-' : '').$whole.'.'.$fraction;
    }

    private static function addIntegers(string $left, string $right): string
    {
        [$leftNegative, $leftAbsolute] = self::parts($left);
        [$rightNegative, $rightAbsolute] = self::parts($right);

        if ($leftNegative === $rightNegative) {
            $sum = self::addAbsolute($leftAbsolute, $rightAbsolute);

            return $leftNegative && $sum !== '0' ? '-'.$sum : $sum;
        }

        $comparison = self::compareAbsolute($leftAbsolute, $rightAbsolute);

        if ($comparison === 0) {
            return '0';
        }

        if ($comparison > 0) {
            $difference = self::subtractAbsolute($leftAbsolute, $rightAbsolute);

            return $leftNegative ? '-'.$difference : $difference;
        }

        $difference = self::subtractAbsolute($rightAbsolute, $leftAbsolute);

        return $rightNegative ? '-'.$difference : $difference;
    }

    private static function compareIntegers(string $left, string $right): int
    {
        [$leftNegative, $leftAbsolute] = self::parts($left);
        [$rightNegative, $rightAbsolute] = self::parts($right);

        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        $comparison = self::compareAbsolute($leftAbsolute, $rightAbsolute);

        return $leftNegative ? -$comparison : $comparison;
    }

    private static function parts(string $number): array
    {
        return [str_starts_with($number, '-'), self::trimAbsolute(ltrim($number, '-'))];
    }

    private static function negate(string $number): string
    {
        return $number === '0' ? '0' : (str_starts_with($number, '-') ? substr($number, 1) : '-'.$number);
    }

    private static function addAbsolute(string $left, string $right): string
    {
        $length = max(strlen($left), strlen($right));
        $left = str_pad($left, $length, '0', STR_PAD_LEFT);
        $right = str_pad($right, $length, '0', STR_PAD_LEFT);
        $carry = 0;
        $result = '';

        for ($index = $length - 1; $index >= 0; $index--) {
            $sum = (int) $left[$index] + (int) $right[$index] + $carry;
            $result = ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return self::trimAbsolute(($carry ? (string) $carry : '').$result);
    }

    private static function subtractAbsolute(string $larger, string $smaller): string
    {
        $length = strlen($larger);
        $smaller = str_pad($smaller, $length, '0', STR_PAD_LEFT);
        $borrow = 0;
        $result = '';

        for ($index = $length - 1; $index >= 0; $index--) {
            $digit = (int) $larger[$index] - $borrow - (int) $smaller[$index];

            if ($digit < 0) {
                $digit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $result = $digit.$result;
        }

        return self::trimAbsolute($result);
    }

    private static function compareAbsolute(string $left, string $right): int
    {
        $left = self::trimAbsolute($left);
        $right = self::trimAbsolute($right);

        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    private static function trimAbsolute(string $number): string
    {
        $number = ltrim($number, '0');

        return $number === '' ? '0' : $number;
    }
}
