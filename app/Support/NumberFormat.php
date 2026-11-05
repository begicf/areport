<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * How the signed-in user writes numbers. Values are stored and exported as plain numbers
 * (1234567.89); the form shows and reads them in the user's style, so "1.250" is one
 * thousand two hundred fifty for someone who writes 1.234,56 and 1.25 for 1,234.56.
 */
class NumberFormat
{
    /** Until a user chooses: point between thousands, comma for decimals. */
    public const DEFAULT = 'eu';

    /** style => [decimal separator, group separator, label] */
    public const STYLES = [
        'eu' => [',', '.', '1.234.567,89'],
        'en' => ['.', ',', '1,234,567.89'],
        'space' => [',', ' ', '1 234 567,89'],
        'ch' => ['.', "'", "1'234'567.89"],
    ];

    public static function style(): string
    {
        $style = Auth::user()->number_format ?? null;

        return isset(self::STYLES[$style]) ? $style : self::DEFAULT;
    }

    /** For the page scripts (window.AREPORT_NUM). */
    public static function js(): array
    {
        [$decimal, $group] = self::STYLES[self::style()];

        return ['style' => self::style(), 'decimal' => $decimal, 'group' => $group];
    }

    /** A plain number (1234567.5) in the user's style (1.234.567,5); anything else unchanged. */
    public static function format($value): string
    {
        $value = (string) $value;
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', trim($value), $m)) {
            return $value;
        }
        [$decimal, $group] = self::STYLES[self::style()];
        $int = preg_replace('/\B(?=(\d{3})+(?!\d))/', $group, $m[2]);

        return $m[1].$int.(isset($m[3]) && $m[3] !== '' ? $decimal.$m[3] : '');
    }
}
