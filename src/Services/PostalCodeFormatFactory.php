<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Data\PostalCodeFormat;

final class PostalCodeFormatFactory
{
    public static function make(?string $countryCode = null): PostalCodeFormat
    {
        $normalizedCountryCode = strtoupper(trim((string) $countryCode));

        return match ($normalizedCountryCode) {
            '', 'BR' => PostalCodeFormat::brazilian(),
            default => PostalCodeFormat::generic(),
        };
    }
}
