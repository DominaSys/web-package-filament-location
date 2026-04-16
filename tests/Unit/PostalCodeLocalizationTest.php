<?php

use Dominasys\FilamentLocation\Data\PostalCodeLookupResult;
use Illuminate\Support\Facades\App;

it('returns pt-br localized notification messages', function () {
    $originalLocale = App::getLocale();

    try {
        App::setLocale('pt_BR');

        $result = PostalCodeLookupResult::invalid(postalCode: '123');

        expect($result->notificationTitle())->toBe('CEP inválido')
            ->and($result->notificationBody())->toBe('O CEP informado é inválido.');
    } finally {
        App::setLocale($originalLocale);
    }
});

it('falls back to english localization', function () {
    $originalLocale = App::getLocale();

    try {
        App::setLocale('en');

        $result = PostalCodeLookupResult::unsupportedCountry(countryCode: 'US');

        expect($result->notificationTitle())->toBe('Unsupported country')
            ->and($result->notificationBody())->toBe('The selected country does not have postal code lookup support yet.');
    } finally {
        App::setLocale($originalLocale);
    }
});
