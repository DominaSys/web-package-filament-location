<?php

use Dominasys\FilamentLocation\Forms\Components\Address\City;
use Dominasys\FilamentLocation\Forms\Components\Address\Complement;
use Dominasys\FilamentLocation\Forms\Components\Address\HouseNumber;
use Dominasys\FilamentLocation\Forms\Components\Address\Neighborhood;
use Dominasys\FilamentLocation\Forms\Components\Address\Number;
use Dominasys\FilamentLocation\Forms\Components\Address\State;
use Dominasys\FilamentLocation\Forms\Components\Address\Street;
use Dominasys\FilamentLocation\Forms\Components\PostalCode;
use Dominasys\FilamentLocation\Support\AccentInsensitiveSelectSearch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

it('creates the address field components', function () {
    expect(State::make('state'))->toBeInstanceOf(Select::class)
        ->and(State::make('state')->getExtraAlpineAttributeBag()->getAttributes())
        ->toHaveKey('x-init');
    expect(City::make('city'))->toBeInstanceOf(Select::class)
        ->and(City::make('city')->getExtraAlpineAttributeBag()->getAttributes())
        ->toHaveKey('x-init');
    expect(Neighborhood::make('neighborhood'))->toBeInstanceOf(TextInput::class);
    expect(Street::make('street'))->toBeInstanceOf(TextInput::class);
    expect(HouseNumber::make('house_number'))->toBeInstanceOf(TextInput::class);
    expect(Number::make('number'))->toBeInstanceOf(TextInput::class);
    expect(Complement::make('complement'))->toBeInstanceOf(TextInput::class);
    expect(PostalCode::make('postal_code'))->toBeInstanceOf(TextInput::class);
});

it('registers global aliases for backwards compatibility', function () {
    expect(class_exists('PostalCode'))->toBeTrue();
    expect(class_exists('State'))->toBeTrue();
    expect(class_exists('City'))->toBeTrue();
    expect(class_exists('HouseNumber'))->toBeTrue();
});

it('builds accent-insensitive fuzzy select search javascript', function () {
    expect(AccentInsensitiveSelectSearch::xInit())
        ->toContain('levenshtein')
        ->and(AccentInsensitiveSelectSearch::xInit())
        ->toContain('matchScore');
});
