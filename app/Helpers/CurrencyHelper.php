<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Convert monetary amount to Indian Currency Words (Lakhs, Crores).
     * e.g., 12500.50 => "Twelve Thousand Five Hundred Rupees and Fifty Paise Only"
     */
    public static function toIndianWords(float|string $amount): string
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            return 'Zero Rupees Only';
        }

        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = self::convertNumberToWords($rupees) . ' Rupees';

        if ($paise > 0) {
            $words .= ' and ' . self::convertNumberToWords($paise) . ' Paise';
        }

        return $words . ' Only';
    }

    private static function convertNumberToWords(int $number): string
    {
        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen'
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        if ($number === 0) {
            return 'Zero';
        }

        $crores = (int) floor($number / 10000000);
        $remainder = $number % 10000000;

        $lakhs = (int) floor($remainder / 100000);
        $remainder = $remainder % 100000;

        $thousands = (int) floor($remainder / 1000);
        $remainder = $remainder % 1000;

        $hundreds = (int) floor($remainder / 100);
        $remainder = $remainder % 100;

        $parts = [];

        if ($crores > 0) {
            $parts[] = self::convertNumberToWords($crores) . ' Crore';
        }

        if ($lakhs > 0) {
            $parts[] = self::convertNumberToWords($lakhs) . ' Lakh';
        }

        if ($thousands > 0) {
            $parts[] = self::convertNumberToWords($thousands) . ' Thousand';
        }

        if ($hundreds > 0) {
            $parts[] = $ones[$hundreds] . ' Hundred';
        }

        if ($remainder > 0) {
            if ($remainder < 20) {
                $parts[] = $ones[$remainder];
            } else {
                $tenDigit = (int) floor($remainder / 10);
                $oneDigit = $remainder % 10;
                $parts[] = $tens[$tenDigit] . ($oneDigit > 0 ? ' ' . $ones[$oneDigit] : '');
            }
        }

        return implode(' ', array_filter($parts));
    }
}

