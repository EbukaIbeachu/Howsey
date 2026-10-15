<?php

namespace App\Support;

class NumberSpellout
{
    public static function spellout($value): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $asNumber = is_numeric($value) ? (float) $value : 0.0;
            $whole = (int) round($asNumber);

            return (string) $formatter->format($whole);
        }

        $asNumber = is_numeric($value) ? (float) $value : 0.0;
        $whole = (int) round($asNumber);

        return self::spelloutInteger($whole);
    }

    private static function spelloutInteger(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        if ($number < 0) {
            return 'minus '.self::spelloutInteger(abs($number));
        }

        $units = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
        ];

        $tens = [
            2 => 'twenty',
            3 => 'thirty',
            4 => 'forty',
            5 => 'fifty',
            6 => 'sixty',
            7 => 'seventy',
            8 => 'eighty',
            9 => 'ninety',
        ];

        $scales = [
            1000000000000 => 'trillion',
            1000000000 => 'billion',
            1000000 => 'million',
            1000 => 'thousand',
            100 => 'hundred',
        ];

        if ($number < 20) {
            return $units[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $remainder = $number % 10;
            if ($remainder === 0) {
                return $tens[$ten];
            }

            return $tens[$ten].'-'.$units[$remainder];
        }

        foreach ($scales as $scaleValue => $scaleName) {
            if ($number >= $scaleValue) {
                $count = intdiv($number, $scaleValue);
                $remainder = $number % $scaleValue;

                $words = self::spelloutInteger($count).' '.$scaleName;
                if ($remainder === 0) {
                    return $words;
                }

                return $words.' '.self::spelloutInteger($remainder);
            }
        }

        return (string) $number;
    }
}

