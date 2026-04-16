<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Services\AddressSources;

use Dominasys\FilamentLocation\Contracts\AddressDataSourceContract;
use Dominasys\FilamentLocation\Support\Translation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class IbgeAddressDataSource implements AddressDataSourceContract
{
    public function sourceKey(): string
    {
        return 'ibge';
    }

    public function supportsCountry(string $countryCode): bool
    {
        return strtoupper(trim($countryCode)) === 'BR';
    }

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $countryCode): array
    {
        $normalizedCountryCode = strtoupper(trim($countryCode));

        if (! $this->supportsCountry($normalizedCountryCode)) {
            throw new RuntimeException(sprintf('IBGE does not support country "%s".', $normalizedCountryCode));
        }

        $states = $this->requestJson('/localidades/estados', ['orderBy' => 'nome']);
        $normalizedStates = [];

        foreach ($states as $state) {
            $stateSourceId = $this->stringifyIdentifier($state['id'] ?? null);
            $stateCode = strtoupper(trim((string) ($state['sigla'] ?? '')));
            $stateName = trim((string) ($state['nome'] ?? ''));
            if ($stateSourceId === '') {
                continue;
            }

            if ($stateCode === '') {
                continue;
            }

            if ($stateName === '') {
                continue;
            }

            $cities = $this->requestJson(sprintf('/localidades/estados/%s/municipios', $stateSourceId), ['orderBy' => 'nome']);
            $normalizedCities = [];

            foreach ($cities as $city) {
                $citySourceId = $this->stringifyIdentifier($city['id'] ?? null);
                $cityName = trim((string) ($city['nome'] ?? ''));
                if ($citySourceId === '') {
                    continue;
                }

                if ($cityName === '') {
                    continue;
                }

                $normalizedCities[] = [
                    'code' => $citySourceId,
                    'label' => $cityName,
                    'identifiers' => [
                        'source' => $this->sourceKey(),
                        'source_id' => $citySourceId,
                        'parent_code' => $stateCode,
                    ],
                ];
            }

            $normalizedStates[] = [
                'code' => $stateCode,
                'label' => $stateName,
                'identifiers' => [
                    'source' => $this->sourceKey(),
                    'source_id' => $stateSourceId,
                    'source_code' => $stateCode,
                ],
                'administrative_divisions' => $this->administrativeDivisions($state['regiao'] ?? null),
                'cities' => $normalizedCities,
            ];
        }

        return [
            'country_code' => $normalizedCountryCode,
            'country_name' => Translation::countryName($normalizedCountryCode) ?? 'Brazil',
            'source' => $this->sourceKey(),
            'states' => $normalizedStates,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function administrativeDivisions(mixed $region): array
    {
        if (! is_array($region)) {
            return [];
        }

        $regionSourceId = $this->stringifyIdentifier($region['id'] ?? null);
        $regionCode = strtoupper(trim((string) ($region['sigla'] ?? '')));
        $regionName = trim((string) ($region['nome'] ?? ''));

        if ($regionSourceId === '' || $regionCode === '' || $regionName === '') {
            return [];
        }

        return [
            [
                'type' => 'region',
                'code' => $regionCode,
                'label' => $regionName,
                'identifiers' => [
                    'source' => $this->sourceKey(),
                    'source_id' => $regionSourceId,
                    'source_code' => $regionCode,
                ],
            ],
        ];
    }

    private function stringifyIdentifier(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function requestJson(string $uri, array $query = []): array
    {
        $baseUrl = rtrim((string) config('location.address_data_sync.sources.br.ibge.base_url', 'https://servicodados.ibge.gov.br/api/v1'), '/');
        $timeout = (int) config('location.address_data_sync.sources.br.ibge.timeout', 20);

        $response = Http::baseUrl($baseUrl)
            ->timeout($timeout)
            ->acceptJson()
            ->get(ltrim($uri, '/'), $query);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('IBGE request failed for "%s" with status %d.', $uri, $response->status()));
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }
}
