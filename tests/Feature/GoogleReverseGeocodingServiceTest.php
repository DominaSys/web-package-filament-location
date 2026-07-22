<?php

use Dominasys\FilamentLocation\Contracts\ReverseGeocodingServiceContract;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('requires the optional google integration to be configured', function () {
    config([
        'location.google.enabled' => false,
        'location.google.server_key' => null,
    ]);

    app(ReverseGeocodingServiceContract::class)->reverse(-23.5614, -46.6559);
})->throws(DomainException::class, 'Google reverse geocoding is not configured.');

it('reverse geocodes only when explicitly invoked and resolves the local ibge code', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
            'status' => 'OK',
            'results' => [[
                'place_id' => 'ChIJ-test',
                'formatted_address' => 'Campinas - SP, Brasil',
                'geometry' => [
                    'location' => ['lat' => -22.9056, 'lng' => -47.0608],
                    'location_type' => 'ROOFTOP',
                ],
                'address_components' => [
                    ['long_name' => 'Campinas', 'short_name' => 'Campinas', 'types' => ['administrative_area_level_2']],
                    ['long_name' => 'São Paulo', 'short_name' => 'SP', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'Brasil', 'short_name' => 'BR', 'types' => ['country']],
                ],
            ]],
        ]),
    ]);

    config([
        'location.google.enabled' => true,
        'location.google.server_key' => 'server-secret',
    ]);

    $place = app(ReverseGeocodingServiceContract::class)->reverse(-22.9056, -47.0608);

    expect($place->city)->toBe('Campinas')
        ->and($place->cityCode)->toBe('3509502')
        ->and($place->placeId)->toBe('ChIJ-test');

    Http::assertSent(fn (Request $request): bool => $request['key'] === 'server-secret'
        && $request['latlng'] === '-22.90560000,-47.06080000');
});

it('caches reverse geocoding by coordinates', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
            'status' => 'OK',
            'results' => [[
                'place_id' => 'cached-place',
                'formatted_address' => 'São Paulo - SP, Brasil',
                'geometry' => [
                    'location' => ['lat' => -23.5, 'lng' => -46.6],
                    'location_type' => 'ROOFTOP',
                ],
                'address_components' => [],
            ]],
        ]),
    ]);
    config([
        'location.google.enabled' => true,
        'location.google.server_key' => 'server-secret',
    ]);

    $service = app(ReverseGeocodingServiceContract::class);

    expect($service->reverse(-23.5, -46.6)->placeId)->toBe('cached-place')
        ->and($service->reverse(-23.5, -46.6)->placeId)->toBe('cached-place');

    Http::assertSentCount(1);
});
