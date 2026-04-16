<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Data\BrazilianAddressData;

final class AddressFieldOptionsFactory
{
    /**
     * @return array<string, string>
     */
    public static function states(?string $countryCode = null): array
    {
        return match (self::normalizeCountryCode($countryCode)) {
            '', 'BR' => BrazilianAddressData::states(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function cities(?string $countryCode = null, ?string $stateCode = null): array
    {
        return match (self::normalizeCountryCode($countryCode)) {
            '', 'BR' => BrazilianAddressData::cities($stateCode),
            default => [],
        };
    }

    private static function normalizeCountryCode(?string $countryCode): string
    {
        return strtoupper(trim((string) $countryCode));
    }
}
