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
            $stateIbgeId = (int) ($state['id'] ?? 0);
            $stateCode = strtoupper(trim((string) ($state['sigla'] ?? '')));
            $stateName = trim((string) ($state['nome'] ?? ''));
            if ($stateIbgeId === 0) {
                continue;
            }

            if ($stateCode === '') {
                continue;
            }

            if ($stateName === '') {
                continue;
            }

            $cities = $this->requestJson(sprintf('/localidades/estados/%d/municipios', $stateIbgeId), ['orderBy' => 'nome']);

            $normalizedCities = [];

            foreach ($cities as $city) {
                $cityName = trim((string) ($city['nome'] ?? ''));
                $cityIbgeId = (int) ($city['id'] ?? 0);
                if ($cityName === '') {
                    continue;
                }

                if ($cityIbgeId === 0) {
                    continue;
                }

                $normalizedCities[] = [
                    'value' => $cityName,
                    'label' => $cityName,
                    'meta' => [
                        'ibge_id' => $cityIbgeId,
                        'state_ibge_id' => $stateIbgeId,
                    ],
                ];
            }

            $region = is_array($state['regiao'] ?? null) ? $state['regiao'] : [];

            $normalizedStates[] = [
                'value' => $stateCode,
                'label' => $stateName,
                'meta' => [
                    'ibge_id' => $stateIbgeId,
                    'region' => [
                        'ibge_id' => (int) ($region['id'] ?? 0),
                        'code' => strtoupper(trim((string) ($region['sigla'] ?? ''))),
                        'name' => trim((string) ($region['nome'] ?? '')),
                    ],
                ],
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
