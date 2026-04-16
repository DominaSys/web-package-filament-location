<?php

use Dominasys\FilamentLocation\Enums\PostalCodeLookupStatusEnum;
use Dominasys\FilamentLocation\Services\BrazilianPostalCodeService;
use Dominasys\FilamentLocation\Services\PostalCodeServiceFactory;
use Dominasys\FilamentLocation\Services\UnsupportedCountryPostalCodeService;
use Illuminate\Support\Facades\Http;

it('resolves the brazilian service from the factory', function () {
    expect(PostalCodeServiceFactory::make('BR'))->toBeInstanceOf(BrazilianPostalCodeService::class);
});

it('falls back to an unsupported country service when the country is unknown', function () {
    expect(PostalCodeServiceFactory::make('US'))->toBeInstanceOf(UnsupportedCountryPostalCodeService::class);
});

it('returns the awesome api payload when it succeeds', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'cep.awesomeapi.com.br')) {
            return Http::response([
                'cep' => '88807215',
                'address' => 'Rua Alceri Maria Gomes da Silva',
                'district' => 'Santa Luzia',
                'city' => 'Criciuma',
                'state' => 'SC',
                'city_ibge' => '4204608',
            ], 200);
        }

        return Http::response([], 500);
    });

    $result = (new BrazilianPostalCodeService)->lookup('88807-215');

    expect($result->isFound())->toBeTrue()
        ->and($result->toArray())->toMatchArray([
            'postal_code' => '88807215',
            'country' => __('filament-location::location.countries.br.name'),
            'country_code' => 'BR',
            'street' => 'Rua Alceri Maria Gomes da Silva',
            'neighborhood' => 'Santa Luzia',
            'city' => 'Criciuma',
            'city_code' => '4204608',
            'state' => null,
            'state_code' => 'SC',
            'ibge_code' => '4204608',
            'status' => PostalCodeLookupStatusEnum::FOUND->value,
            'source' => 'awesomeapi',
        ]);
});

it('falls back to the next api when the first one fails', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'cep.awesomeapi.com.br')) {
            return Http::response([
                'code' => 'not_found',
            ], 404);
        }

        if (str_contains($request->url(), 'opencep.com')) {
            return Http::response([
                'cep' => '88807-215',
                'logradouro' => 'Rua Alceri Maria Gomes da Silva',
                'bairro' => 'Santa Luzia',
                'localidade' => 'Criciuma',
                'uf' => 'SC',
                'ibge' => '4204608',
            ], 200);
        }

        throw new RuntimeException('Unexpected request: ' . $request->url());
    });

    $result = (new BrazilianPostalCodeService)->lookup('88807-215');

    expect($result->isFound())->toBeTrue()
        ->and($result->toArray())->toMatchArray([
            'postal_code' => '88807215',
            'country' => __('filament-location::location.countries.br.name'),
            'country_code' => 'BR',
            'street' => 'Rua Alceri Maria Gomes da Silva',
            'neighborhood' => 'Santa Luzia',
            'city' => 'Criciuma',
            'city_code' => '4204608',
            'state' => null,
            'state_code' => 'SC',
            'ibge_code' => '4204608',
            'status' => PostalCodeLookupStatusEnum::FOUND->value,
            'source' => 'opencep',
        ]);
});

it('returns invalid when the cep format is invalid', function () {
    $result = (new BrazilianPostalCodeService)->lookup('123');

    expect($result->isInvalid())->toBeTrue()
        ->and($result->toArray())->toMatchArray([
            'postal_code' => '123',
            'country' => __('filament-location::location.countries.br.name'),
            'country_code' => 'BR',
            'status' => PostalCodeLookupStatusEnum::INVALID->value,
        ]);
});

it('returns an empty payload state when no service can resolve the cep', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'cep.awesomeapi.com.br')) {
            return Http::response([
                'code' => 'invalid',
            ], 400);
        }

        if (str_contains($request->url(), 'opencep.com')) {
            return Http::response([
                'error' => true,
            ], 404);
        }

        if (str_contains($request->url(), 'viacep.com.br')) {
            return Http::response([
                'erro' => 'true',
            ], 200);
        }

        if (str_contains($request->url(), 'brasilapi.com.br')) {
            return Http::response([
                'name' => 'CepPromiseError',
                'message' => 'Todos os servicos de CEP retornaram erro.',
                'type' => 'service_error',
                'errors' => [
                    [
                        'name' => 'ServiceError',
                        'message' => 'Erro ao se conectar com o servico ViaCEP.',
                        'service' => 'viacep',
                    ],
                ],
            ], 404);
        }

        throw new RuntimeException('Unexpected request: ' . $request->url());
    });

    $result = (new BrazilianPostalCodeService)->lookup('00000-000');

    expect($result->isNotFound())->toBeTrue()
        ->and($result->toArray())->toMatchArray([
            'postal_code' => '00000000',
            'country' => __('filament-location::location.countries.br.name'),
            'country_code' => 'BR',
            'status' => PostalCodeLookupStatusEnum::NOT_FOUND->value,
        ]);
});
