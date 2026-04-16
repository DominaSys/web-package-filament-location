<?php

use Dominasys\FilamentLocation\Services\BrazilianPostalCodeService;
use Illuminate\Support\Facades\Http;

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

    expect(BrazilianPostalCodeService::get('88807-215'))->toBe([
        'street' => 'Rua Alceri Maria Gomes da Silva',
        'neighborhood' => 'Santa Luzia',
        'city' => 'Criciuma',
        'state' => null,
        'state_code' => 'SC',
        'ibge_code' => '4204608',
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

    expect(BrazilianPostalCodeService::get('88807-215'))->toBe([
        'street' => 'Rua Alceri Maria Gomes da Silva',
        'neighborhood' => 'Santa Luzia',
        'city' => 'Criciuma',
        'state' => null,
        'state_code' => 'SC',
        'ibge_code' => '4204608',
    ]);
});

it('returns an empty array when no service can resolve the cep', function () {
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
                'message' => 'Todos os serviços de CEP retornaram erro.',
                'type' => 'service_error',
                'errors' => [
                    [
                        'name' => 'ServiceError',
                        'message' => 'Erro ao se conectar com o serviço ViaCEP.',
                        'service' => 'viacep',
                    ],
                ],
            ], 404);
        }

        throw new RuntimeException('Unexpected request: ' . $request->url());
    });

    expect(BrazilianPostalCodeService::get('00000-000'))->toBe([]);
});
