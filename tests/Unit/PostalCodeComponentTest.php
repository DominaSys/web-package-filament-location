<?php

use Dominasys\FilamentLocation\Data\PostalCodeLookupResult;
use Dominasys\FilamentLocation\Forms\Components\PostalCode;
use Dominasys\FilamentLocation\Services\BrazilianPostalCodeService;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component as LivewireComponent;

beforeEach(function () {
    app()->forgetInstance(BrazilianPostalCodeService::class);
    app()->instance(BrazilianPostalCodeService::class, new class implements \Dominasys\FilamentLocation\Contracts\PostalCodeServiceContract
    {
        public function lookup(string $postalCode): PostalCodeLookupResult
        {
            return PostalCodeLookupResult::found(
                postalCode: '88807215',
                country: 'Brazil',
                countryCode: 'BR',
                state: 'Santa Catarina',
                stateCode: 'SC',
                city: 'Criciuma',
                cityCode: '4204608',
                neighborhood: 'Centro',
                street: 'Rua X',
                ibgeCode: '4204608',
                source: 'fake',
            );
        }
    });
});

afterEach(function () {
    app()->forgetInstance(BrazilianPostalCodeService::class);
});

it('generates safe focus javascript when the target field is missing', function () {
    $livewire = new class extends LivewireComponent
    {
        public ?string $expression = null;

        public function js($expression, ...$params)
        {
            $this->expression = $expression;
        }
    };

    $component = PostalCode::make('postal_code');
    $method = new ReflectionMethod($component, 'focusElement');
    $method->setAccessible(true);
    $method->invoke($component, $livewire, 'address_number');

    expect($livewire->expression)
        ->toContain('document.getElementById("address_number")')
        ->toContain('if (element) {')
        ->toContain('element.focus();');
});

it('dehydrates postal code values as digits only', function () {
    $component = PostalCode::make('postal_code');

    $maskProperty = new ReflectionProperty($component, 'dehydrateMask');
    $maskProperty->setAccessible(true);

    $callbackProperty = new ReflectionProperty($component, 'dehydrateStateUsing');
    $callbackProperty->setAccessible(true);

    $callback = $callbackProperty->getValue($component);

    expect($maskProperty->getValue($component))->toBeFalse()
        ->and($callback('88807-215'))->toBe('88807-215')
        ->and($callback(null))->toBeNull();

    $component->dehydrateMask();

    expect($maskProperty->getValue($component))->toBeTrue()
        ->and($callback('88807-215'))->toBe('88807215')
        ->and($callback(null))->toBeNull();
});

it('disables the postal code action when the field is disabled', function () {
    $component = PostalCode::make('postal_code')->disabled();

    $method = new ReflectionMethod($component, 'makePostalCodeAction');
    $method->setAccessible(true);

    $action = $method->invoke($component);

    expect($action->isDisabled())->toBeTrue();
});

it('syncs the city label field during postal code lookup', function () {
    $livewire = new class extends LivewireComponent
    {
        public function js($expression, ...$params)
        {
        }
    };

    $component = new class('postal_code') extends PostalCode
    {
        public function getState(): mixed
        {
            return '88807-215';
        }

        public function getKey(bool $isAbsolute = true): ?string
        {
            return null;
        }
    };

    $component->bindCityField('city')
        ->bindCityCodeField('city_code');

    $get = mock(Get::class);
    $get->shouldReceive('__invoke')
        ->andReturnUsing(function (string $key): ?string {
            return match ($key) {
                'country_code' => 'BR',
                default => null,
            };
        });

    $set = mock(Set::class);
    $set->shouldReceive('__invoke')->once()->with('street', 'Rua X');
    $set->shouldReceive('__invoke')->once()->with('neighborhood', 'Centro');
    $set->shouldReceive('__invoke')->once()->with('state', 'Santa Catarina');
    $set->shouldReceive('__invoke')->once()->with('state_code', 'SC');
    $set->shouldReceive('__invoke')->once()->with('city', 'Criciuma', false, true);
    $set->shouldReceive('__invoke')->once()->with('city_code', '4204608', false, true);
    $set->shouldReceive('__invoke')->once()->with('country', 'Brazil');
    $set->shouldReceive('__invoke')->once()->with('country_code', 'BR');

    $method = new ReflectionMethod($component, 'getPostalCode');
    $method->setAccessible(true);
    $method->invoke($component, $livewire, $component, $get, $set);
});
