<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\PostalCodeServiceContract;

final class PostalCodeServiceFactory
{
    public static function make(?string $countryCode = null): PostalCodeServiceContract
    {
        $normalizedCountryCode = strtoupper(trim((string) $countryCode));

        return match ($normalizedCountryCode) {
            '', 'BR' => app(BrazilianPostalCodeService::class),
            default => new UnsupportedCountryPostalCodeService($normalizedCountryCode),
        };
    }
}
