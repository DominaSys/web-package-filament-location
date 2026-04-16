<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Support;

use Illuminate\Support\Facades\Lang;

final class Translation
{
    public static function get(string $key): string
    {
        $translationKey = self::packageKey($key);

        foreach ([app()->getLocale(), 'en'] as $locale) {
            $value = Lang::get($translationKey, [], $locale);

            if (is_string($value) && $value !== $translationKey) {
                return $value;
            }
        }

        return $key;
    }

    public static function countryName(string $countryCode): ?string
    {
        $countryCode = strtolower(trim($countryCode));

        if ($countryCode === '') {
            return null;
        }

        $translation = self::get(sprintf('countries.%s.name', $countryCode));

        return str_contains($translation, 'countries.') ? null : $translation;
    }

    private static function packageKey(string $key): string
    {
        return 'filament-location::location.' . $key;
    }
}
