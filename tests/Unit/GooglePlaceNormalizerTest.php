<?php

use Dominasys\FilamentLocation\Services\GooglePlaceNormalizer;

it('normalizes only the persisted google place fields', function () {
    $place = app(GooglePlaceNormalizer::class)->normalize([
        'place_id' => 'ChIJ-test',
        'formatted_address' => 'Av. Paulista, 1000 - São Paulo, SP, Brasil',
        'geometry' => [
            'location' => ['lat' => -23.5614, 'lng' => -46.6559],
            'location_type' => 'ROOFTOP',
        ],
        'address_components' => [
            ['long_name' => '1000', 'short_name' => '1000', 'types' => ['street_number']],
            ['long_name' => 'Avenida Paulista', 'short_name' => 'Av. Paulista', 'types' => ['route']],
            ['long_name' => 'Bela Vista', 'short_name' => 'Bela Vista', 'types' => ['sublocality_level_1']],
            ['long_name' => 'São Paulo', 'short_name' => 'São Paulo', 'types' => ['administrative_area_level_2']],
            ['long_name' => 'São Paulo', 'short_name' => 'SP', 'types' => ['administrative_area_level_1']],
            ['long_name' => 'Brasil', 'short_name' => 'BR', 'types' => ['country']],
            ['long_name' => '01310-100', 'short_name' => '01310-100', 'types' => ['postal_code']],
        ],
        'unrequested_raw_payload' => ['must_not' => 'persist'],
    ]);

    expect($place->toArray())
        ->toMatchArray([
            'place_id' => 'ChIJ-test',
            'latitude' => -23.5614,
            'longitude' => -46.6559,
            'city' => 'São Paulo',
            'state_code' => 'SP',
            'country_code' => 'BR',
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'precision' => 'rooftop',
        ])
        ->not->toHaveKey('unrequested_raw_payload');
});

it('accepts the normalized browser shape', function () {
    $place = app(GooglePlaceNormalizer::class)->normalize([
        'id' => 'place-1',
        'formattedAddress' => 'Rua X, Campinas - SP',
        'location' => ['lat' => '-22.90', 'lng' => '-47.06'],
        'city' => 'Campinas',
        'state_code' => 'SP',
        'country_code' => 'BR',
    ]);

    expect($place->placeId)->toBe('place-1')
        ->and($place->city)->toBe('Campinas')
        ->and($place->latitude)->toBe(-22.9);
});
