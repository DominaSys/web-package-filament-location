<?php

declare(strict_types=1);

namespace Dominasys\FilamentLocation\Tests\Support;

use Dominasys\FilamentLocation\Contracts\AddressDataSourceContract;

final class FakeAddressDataSource implements AddressDataSourceContract
{
    public function sourceKey(): string
    {
        return 'fake';
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
        return [
            'country_code' => strtoupper(trim($countryCode)),
            'country_name' => 'Brasil',
            'source' => $this->sourceKey(),
            'states' => [
                [
                    'value' => 'SP',
                    'label' => 'São Paulo',
                    'meta' => [
                        'ibge_id' => 35,
                        'region' => [
                            'ibge_id' => 3,
                            'code' => 'SE',
                            'name' => 'Sudeste',
                        ],
                    ],
                    'cities' => [
                        [
                            'value' => 'Campinas',
                            'label' => 'Campinas',
                            'meta' => [
                                'ibge_id' => 3509502,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
