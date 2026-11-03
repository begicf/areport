<?php

namespace App\Support;

/** ISO 3166-1 alpha-2 country codes, as reports and entities carry them. */
class Country
{
    public static function isValid(?string $code): bool
    {
        $code = strtoupper(trim((string) $code));
        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return false;
        }
        // ICU names every assigned region; an unknown code comes back as itself ("XX").
        return ! class_exists(\Locale::class) || \Locale::getDisplayRegion('-'.$code, 'en') !== $code;
    }

    /** A validation rule: empty passes (nullable), anything else must be a country. */
    public static function rule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value !== null && $value !== '' && ! self::isValid((string) $value)) {
                $fail('Enter a two-letter ISO country code, e.g. FR or BA.');
            }
        };
    }
}
