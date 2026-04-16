<?php

use Dominasys\FilamentLocation\Services\AddressFieldOptionsFactory;

it('returns brazilian states', function () {
    $states = AddressFieldOptionsFactory::states('BR');

    expect($states)
        ->toHaveKey('SP')
        ->and($states['SP'])
        ->toBe('São Paulo');
});

it('returns brazilian cities for a state', function () {
    $cities = AddressFieldOptionsFactory::cities('BR', 'SP');

    expect($cities)
        ->toHaveKey('Campinas')
        ->toHaveKey('São Paulo');
});

it('returns empty options for unsupported countries', function () {
    expect(AddressFieldOptionsFactory::states('US'))->toBe([]);
    expect(AddressFieldOptionsFactory::cities('US', 'CA'))->toBe([]);
});
