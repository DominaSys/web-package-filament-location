<?php

use Dominasys\FilamentLocation\Services\PostalCodeFormatFactory;

it('returns the brazilian postal code format for br', function () {
    $format = PostalCodeFormatFactory::make('BR');

    expect($format->mask)->toBe('99999-999')
        ->and($format->minLength)->toBe(9)
        ->and($format->maxLength)->toBe(9)
        ->and($format->validationRules())->toBe(['min:9', 'max:9']);
});

it('returns a generic postal code format for unknown countries', function () {
    $format = PostalCodeFormatFactory::make('US');

    expect($format->mask)->toBeNull()
        ->and($format->minLength)->toBeNull()
        ->and($format->maxLength)->toBeNull()
        ->and($format->validationRules())->toBe([]);
});
