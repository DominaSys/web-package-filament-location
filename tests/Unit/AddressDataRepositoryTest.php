<?php

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;
use Dominasys\FilamentLocation\Services\JsonAddressDataRepository;
use Illuminate\Support\Facades\Cache;

it('loads brazilian states and cities from the json repository', function () {
    $repository = app(AddressDataRepositoryContract::class);

    expect($repository)->toBeInstanceOf(JsonAddressDataRepository::class);
    expect($repository->states('BR'))
        ->toHaveKey('SP')
        ->and($repository->states('BR')['SP'])
        ->toBe('São Paulo');

    expect($repository->cities('BR', 'SP'))
        ->toHaveKey('Campinas')
        ->and($repository->cities('BR', 'SP')['São Paulo'])
        ->toBe('São Paulo');
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
