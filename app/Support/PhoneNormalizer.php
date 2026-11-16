<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalize Kenyan (and common) phone numbers to digits with country code (254...).
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Local Kenyan mobile: 07xxxxxxxx / 01xxxxxxxx
        if (preg_match('/^0([17]\d{8})$/', $digits, $matches)) {
            return '254'.$matches[1];
        }

        // Already with country code 254...
        if (preg_match('/^254([17]\d{8})$/', $digits)) {
            return $digits;
        }

        // +254 without leading 0 already stripped above
        if (strlen($digits) === 9 && preg_match('/^[17]\d{8}$/', $digits)) {
            return '254'.$digits;
        }

        return $digits;
    }

    public static function formatDisplay(?string $normalized): ?string
    {
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (preg_match('/^254(\d{9})$/', $normalized, $matches)) {
            return '+254 '.$matches[1];
        }

        return $normalized;
    }
}
