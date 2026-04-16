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
                    'code' => 'SP',
                    'label' => 'São Paulo',
                    'identifiers' => [
                        'source' => $this->sourceKey(),
                        'source_id' => '35',
                        'source_code' => 'SP',
                    ],
                    'administrative_divisions' => [
                        [
                            'type' => 'region',
                            'code' => 'SE',
                            'label' => 'Sudeste',
                            'identifiers' => [
                                'source' => $this->sourceKey(),
                                'source_id' => '3',
                                'source_code' => 'SE',
                            ],
                        ],
                    ],
                    'cities' => [
                        [
                            'code' => '3509502',
                            'label' => 'Campinas',
                            'identifiers' => [
                                'source' => $this->sourceKey(),
                                'source_id' => '3509502',
                                'parent_code' => 'SP',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
