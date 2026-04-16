<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Illuminate\Support\Str;

final class AddressDataPathResolver
{
    public function baseDirectory(): string
    {
        $configuredBasePath = trim((string) config('location.address_data_path', ''));

        if ($configuredBasePath !== '') {
            return rtrim($configuredBasePath, DIRECTORY_SEPARATOR);
        }

        return dirname(__DIR__, 2) . '/resources/data/addresses';
    }

    public function fileForCountry(string $countryCode): string
    {
        return $this->baseDirectory() . DIRECTORY_SEPARATOR . Str::lower($countryCode) . '.json';
    }
}
