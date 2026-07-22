<?php

use Dominasys\FilamentLocation\Contracts\AddressDataRepositoryContract;
use Dominasys\FilamentLocation\Services\IbgeMunicipalityResolver;

it('resolves the ibge code accent insensitively from local address data', function () {
    $repository = new class implements AddressDataRepositoryContract
    {
        public function states(?string $countryCode = null): array
        {
            return ['SP' => 'São Paulo'];
        }

        public function cities(?string $countryCode = null, ?string $stateCode = null): array
        {
            return ['3550308' => 'São Paulo', '3509502' => 'Campinas'];
        }
    };

    $resolver = new IbgeMunicipalityResolver($repository);

    expect($resolver->resolve('sao paulo', 'sp'))->toBe('3550308')
        ->and($resolver->resolve('Campinas', 'SP'))->toBe('3509502')
        ->and($resolver->resolve('Campinas', 'SP', 'US'))->toBeNull();
});
