<?php

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;
use Dominasys\FilamentLocation\Services\JsonAddressDataRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('loads brazilian states and cities from the json repository', function () {
    $repository = app(AddressDataRepositoryContract::class);

    expect($repository)->toBeInstanceOf(JsonAddressDataRepository::class);
    expect($repository->states('BR'))
        ->toHaveKey('SP')
        ->and($repository->states('BR')['SP'])
        ->toBe('São Paulo');

    expect($repository->cities('BR', 'SP'))
        ->toHaveKey('3509502')
        ->and($repository->cities('BR', 'SP')['3509502'])
        ->toBe('Campinas');
});

it('returns empty options for unsupported countries', function () {
    expect(AddressFieldOptionsFactory::states('US'))->toBe([]);
    expect(AddressFieldOptionsFactory::cities('US', 'CA'))->toBe([]);
});

it('caches country data in memory and via cache store', function () {
    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(function (string $key, mixed $ttl, callable $callback): array {
            return $callback();
        });

    $repository = app(AddressDataRepositoryContract::class);

    expect($repository->states('BR'))->not->toBeEmpty();
    expect($repository->states('BR'))->not->toBeEmpty();
});

it('normalizes rich datasets into select options', function () {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filament-location-repo-' . Str::uuid();
    File::ensureDirectoryExists($directory);

    File::put($directory . DIRECTORY_SEPARATOR . 'br.json', json_encode([
        'country_code' => 'BR',
        'source' => 'fake',
        'states' => [
            [
                'code' => 'SP',
                'label' => 'São Paulo',
                'identifiers' => [
                    'source' => 'fake',
                    'source_id' => '35',
                    'source_code' => 'SP',
                ],
                'cities' => [
                    [
                        'code' => '3509502',
                        'label' => 'Campinas',
                        'identifiers' => [
                            'source' => 'fake',
                            'source_id' => '3509502',
                            'parent_code' => 'SP',
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    config([
        'location.address_data_path' => $directory,
        'location.address_data_cache_enabled' => false,
    ]);

    expect(AddressFieldOptionsFactory::states('BR'))->toMatchArray([
        'SP' => 'São Paulo',
    ]);

    expect(AddressFieldOptionsFactory::cities('BR', 'SP'))->toMatchArray([
        '3509502' => 'Campinas',
    ]);

    File::deleteDirectory($directory);
});
