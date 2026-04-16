<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services;

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

final class JsonAddressDataRepository implements AddressDataRepositoryContract
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $loadedCountries = [];

    public function __construct(
        private readonly AddressDataPathResolver $pathResolver,
        private readonly AddressDataCacheManager $cacheManager,
    ) {}

    public function states(?string $countryCode = null): array
    {
        $countryData = $this->countryData($countryCode);
        $states = Arr::get($countryData, 'states', []);

        if (! is_array($states)) {
            return [];
        }

        $normalizedStates = [];

        foreach ($states as $key => $state) {
            if (is_string($state)) {
                $normalizedStates[$key] = $state;

                continue;
            }

            if (! is_array($state)) {
                continue;
            }

            $value = $this->extractValue($state, ['value', 'code', 'acronym', 'sigla']);
            if ($value === null && is_string($key) && trim($key) !== '') {
                $value = trim($key);
            }

            $label = $this->extractValue($state, ['label', 'name', 'nome']) ?? $value;
            if ($value === null) {
                continue;
            }

            if ($label === null) {
                continue;
            }

            $normalizedStates[$value] = $label;
        }

        return $normalizedStates;
    }

    public function cities(?string $countryCode = null, ?string $stateCode = null): array
    {
        $countryData = $this->countryData($countryCode);
        $stateCode = $this->normalizeStateCode($stateCode);
        $cities = $this->findCitiesForState($countryData, $stateCode);

        if (! is_array($cities)) {
            return [];
        }

        $normalizedCities = [];

        foreach ($cities as $key => $city) {
            if (is_string($city)) {
                $normalizedCities[$city] = $city;

                continue;
            }

            if (! is_array($city)) {
                continue;
            }

            $value = $this->extractValue($city, ['value', 'code', 'name', 'nome']);
            if ($value === null && is_string($key) && trim($key) !== '') {
                $value = trim($key);
            }

            $label = $this->extractValue($city, ['label', 'name', 'nome']) ?? $value;
            if ($value === null) {
                continue;
            }

            if ($label === null) {
                continue;
            }

            $normalizedCities[$value] = $label;
        }

        return $normalizedCities;
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
        $cacheKey = $this->cacheManager->keyForCountry($normalizedCountryCode);

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
        return $this->pathResolver->fileForCountry($countryCode);
    }

    private function normalizeCountryCode(?string $countryCode): string
    {
        return strtoupper(trim((string) $countryCode));
    }

    private function normalizeStateCode(?string $stateCode): string
    {
        return strtoupper(trim((string) $stateCode));
    }

    /**
     * @param  array<string, mixed>  $countryData
     * @return array<int|string, mixed>|null
     */
    private function findCitiesForState(array $countryData, string $stateCode): ?array
    {
        $states = Arr::get($countryData, 'states', []);

        if (! is_array($states)) {
            return Arr::get($countryData, 'cities.' . $stateCode, []);
        }

        if ($this->isStateCollectionRich($states)) {
            foreach ($states as $key => $state) {
                if (! is_array($state)) {
                    continue;
                }

                $value = $this->extractValue($state, ['value', 'code', 'acronym', 'sigla']);
                if ($value === null && is_string($key) && trim($key) !== '') {
                    $value = trim($key);
                }

                if ($value === null) {
                    continue;
                }

                if (strtoupper($value) !== $stateCode) {
                    continue;
                }

                return is_array($state['cities'] ?? null) ? $state['cities'] : [];
            }

            return [];
        }

        return Arr::get($countryData, 'cities.' . $stateCode, []);
    }

    /**
     * @param  array<int|string, mixed>  $states
     */
    private function isStateCollectionRich(array $states): bool
    {
        foreach ($states as $state) {
            if (is_array($state)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function extractValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
