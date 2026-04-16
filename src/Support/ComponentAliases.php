<?php

declare(strict_types=1);

use Dominasys\FilamentLocation\Forms\Components\Address\City;
use Dominasys\FilamentLocation\Forms\Components\Address\Complement;
use Dominasys\FilamentLocation\Forms\Components\Address\HouseNumber;
use Dominasys\FilamentLocation\Forms\Components\Address\Neighborhood;
use Dominasys\FilamentLocation\Forms\Components\Address\State;
use Dominasys\FilamentLocation\Forms\Components\Address\Street;
use Dominasys\FilamentLocation\Forms\Components\PostalCode;

foreach ([
    'PostalCode' => PostalCode::class,
    'State' => State::class,
    'City' => City::class,
    'Neighborhood' => Neighborhood::class,
    'Street' => Street::class,
    'HouseNumber' => HouseNumber::class,
    'Number' => HouseNumber::class,
    'Complement' => Complement::class,
] as $alias => $class) {
    if (! class_exists($alias, false) && class_exists($class)) {
        class_alias($class, $alias);
    }
}
