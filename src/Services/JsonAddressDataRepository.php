<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class JsonAddressDataRepository implements AddressDataRepositoryContract
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $loadedCountries = [];

    public function states(?string $countryCode = null): array
    {
        $countryData = $this->countryData($countryCode);
        $states = Arr::get($countryData, 'states', []);

        return is_array($states) ? $states : [];
    }

    public function cities(?string $countryCode = null, ?string $stateCode = null): array
    {
        $countryData = $this->countryData($countryCode);
        $cities = Arr::get($countryData, 'cities.' . $this->normalizeStateCode($stateCode), []);

        if (! is_array($cities)) {
            return [];
        }

        $cities = array_values(array_filter($cities, static fn ($city): bool => is_string($city) && trim($city) !== ''));

        return array_combine($cities, $cities);
    }

    /**
     * @return array<string, mixed>
     */
    private function countryData(?string $countryCode = null): array
    {
        $normalizedCountryCode = $this->normalizeCountryCode($countryCode);

        if (array_key_exists($normalizedCountryCode, $this->loadedCountries)) {
            return $this->loadedCountries[$normalizedCountryCode];
        }

        if ($normalizedCountryCode === '') {
            return $this->loadedCountries[$normalizedCountryCode] = [];
        }

        $cacheEnabled = (bool) config('location.address_data_cache_enabled', true);
        $cacheKey = $this->cacheKey($normalizedCountryCode);

        $data = $cacheEnabled
            ? Cache::remember(
                $cacheKey,
                now()->addSeconds((int) config('location.address_data_cache_ttl', 86400)),
                fn (): array => $this->readCountryData($normalizedCountryCode),
            )
            : $this->readCountryData($normalizedCountryCode);

        return $this->loadedCountries[$normalizedCountryCode] = $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function readCountryData(string $countryCode): array
    {
        $path = $this->resolveCountryPath($countryCode);

        if (! is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);

        if ($json === false || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveCountryPath(string $countryCode): string
    {
        $configuredBasePath = trim((string) config('location.address_data_path', ''));

        if ($configuredBasePath !== '') {
            return rtrim($configuredBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . Str::lower($countryCode) . '.json';
        }

        return dirname(__DIR__, 2) . '/resources/data/addresses/' . Str::lower($countryCode) . '.json';
    }

    private function cacheKey(string $countryCode): string
    {
        $prefix = trim((string) config('location.address_data_cache_key_prefix', 'filament-location.address-data'));

        return $prefix . '.' . Str::lower($countryCode);
    }

    private function normalizeCountryCode(?string $countryCode): string
    {
        return strtoupper(trim((string) $countryCode));
    }

    private function normalizeStateCode(?string $stateCode): string
    {
        return strtoupper(trim((string) $stateCode));
    }
}
