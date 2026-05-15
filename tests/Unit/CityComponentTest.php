<?php

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Dominasys\FilamentLocation\Forms\Components\Address\City;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

beforeEach(function () {
    app()->forgetInstance(AddressDataRepositoryContract::class);
    app()->instance(AddressDataRepositoryContract::class, new class implements AddressDataRepositoryContract
    {
        public function states(?string $countryCode = null): array
        {
            return [];
        }

        public function cities(?string $countryCode = null, ?string $stateCode = null): array
        {
            return [
                '4204608' => 'Criciuma',
                '4202404' => 'Blumenau',
            ];
        }
    });
});

afterEach(function () {
    app()->forgetInstance(AddressDataRepositoryContract::class);
});

it('keeps city codes as the select value by default', function () {
    $component = City::make('city')->bindCityCodeField('city_ibge');

    $options = invokeCityMethod($component, 'resolveOptions', fakeGet());

    expect($options)->toBe([
        '4204608' => 'Criciuma',
        '4202404' => 'Blumenau',
    ]);

    $set = fakeSet()->shouldReceive('__invoke')
        ->once()
        ->with('city_ibge', '4204608')
        ->getMock();

    invokeCityMethod($component, 'syncCityCodeField', $set, fakeGet(), '4204608');
});

it('can use the city label as the select value and sync the hidden city code', function () {
    $component = City::make('city')
        ->bindCityCodeField('city_ibge')
        ->useLabelAsValue();

    $options = invokeCityMethod($component, 'resolveOptions', fakeGet());

    expect($options)->toBe([
        'Criciuma' => 'Criciuma',
        'Blumenau' => 'Blumenau',
    ]);

    expect(invokeCityMethod($component, 'resolveCityCodeByLabel', fakeGet(), 'Criciuma'))->toBe('4204608');

    $set = fakeSet()->shouldReceive('__invoke')
        ->once()
        ->with('city_ibge', '4204608')
        ->getMock();

    invokeCityMethod($component, 'syncCityCodeField', $set, fakeGet(), 'Criciuma');
});

function fakeGet(): Get
{
    return mock(Get::class)
        ->shouldReceive('__invoke')
        ->andReturnUsing(function (string $key): ?string {
            return match ($key) {
                'country_code' => 'BR',
                'state_code' => 'SC',
                default => null,
            };
        })
        ->getMock();
}

function fakeSet(): Set
{
    return mock(Set::class);
}

function invokeCityMethod(City $component, string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod($component, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($component, ...$arguments);
}
