<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;

final class AddressFieldOptionsFactory
{
    private static function repository(): AddressDataRepositoryContract
    {
        return app(AddressDataRepositoryContract::class);
    }

    /**
     * @return array<string, string>
     */
    public static function states(?string $countryCode = null): array
    {
        return self::repository()->states($countryCode);
    }

    /**
     * @return array<string, string>
     */
    public static function cities(?string $countryCode = null, ?string $stateCode = null): array
    {
        return self::repository()->cities($countryCode, $stateCode);
    }
}
