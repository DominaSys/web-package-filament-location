<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\AddressDataSourceContract;
use Dominasys\FilamentLocation\Contracts\AddressDataSourceFactoryContract;
use InvalidArgumentException;

final class AddressDataSourceFactory implements AddressDataSourceFactoryContract
{
    public function make(string $countryCode, ?string $sourceKey = null): AddressDataSourceContract
    {
        $normalizedCountryCode = $this->normalizeCountryCode($countryCode);
        $normalizedSourceKey = $this->normalizeSourceKey($sourceKey);
        $configuredSourceKey = $normalizedSourceKey !== ''
            ? $normalizedSourceKey
            : $this->defaultSourceKey();

        $sourceClass = $this->resolveSourceClass($normalizedCountryCode, $configuredSourceKey);

        if ($sourceClass === null) {
            throw new InvalidArgumentException(sprintf(
                'No address data source configured for country "%s" and source "%s".',
                $normalizedCountryCode,
                $configuredSourceKey
            ));
        }

        return app($sourceClass);
    }

    private function resolveSourceClass(string $countryCode, string $sourceKey): ?string
    {
        $sourceClass = config(sprintf('location.address_data_sync.sources.%s.%s.class', $countryCode, $sourceKey));

        return is_string($sourceClass) && $sourceClass !== '' ? $sourceClass : null;
    }

    private function defaultSourceKey(): string
    {
        return strtolower(trim((string) config('location.address_data_sync.default_source', '')));
    }

    private function normalizeCountryCode(string $countryCode): string
    {
        return strtolower(trim($countryCode));
    }

    private function normalizeSourceKey(?string $sourceKey): string
    {
        return strtolower(trim((string) $sourceKey));
    }
}
