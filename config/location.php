<?php

use Dominasys\FilamentLocation\Services\AddressSources\IbgeAddressDataSource;

// config for Dominasys/FilamentLocation
return [
    'address_data_path' => null,
    'address_data_cache_enabled' => true,
    'address_data_cache_ttl' => 86400,
    'address_data_cache_key_prefix' => 'filament-location.address-data',
    'address_data_sync' => [
        'default_source' => 'ibge',
        'sources' => [
            'br' => [
                'ibge' => [
                    'class' => IbgeAddressDataSource::class,
                    'base_url' => $_ENV['IBGE_REST_INTEGRATION_HOST'] ?? 'https://servicodados.ibge.gov.br/api/v1',
                    'timeout' => $_ENV['IBGE_REST_INTEGRATION_TIMEOUT'] ?? 20,
                ],
            ],
        ],
    ],
];
