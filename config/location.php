<?php

use Dominasys\FilamentLocation\Services\AddressSources\IbgeAddressDataSource;

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
                    'base_url' => env('IBGE_REST_INTEGRATION_HOST', 'https://servicodados.ibge.gov.br/api/v1'),
                    'timeout' => env('IBGE_REST_INTEGRATION_TIMEOUT', 20),
                ],
            ],
        ],
    ],
    'google' => [
        'enabled' => filter_var(env('FILAMENT_LOCATION_GOOGLE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'browser_key' => env('FILAMENT_LOCATION_GOOGLE_BROWSER_KEY'),
        'server_key' => env('FILAMENT_LOCATION_GOOGLE_SERVER_KEY'),
        'embed_key' => env('FILAMENT_LOCATION_GOOGLE_EMBED_KEY'),
        'map_id' => env('FILAMENT_LOCATION_GOOGLE_MAP_ID'),
        'language' => env('FILAMENT_LOCATION_GOOGLE_LANGUAGE', 'pt-BR'),
        'region' => env('FILAMENT_LOCATION_GOOGLE_REGION', 'BR'),
        'geocoding_url' => env('FILAMENT_LOCATION_GOOGLE_GEOCODING_URL', 'https://maps.googleapis.com/maps/api/geocode/json'),
        'connect_timeout' => (int) env('FILAMENT_LOCATION_GOOGLE_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('FILAMENT_LOCATION_GOOGLE_TIMEOUT', 10),
        'reverse_geocoding_cache_ttl' => (int) env('FILAMENT_LOCATION_GOOGLE_REVERSE_GEOCODING_CACHE_TTL', 86400),
    ],
];
