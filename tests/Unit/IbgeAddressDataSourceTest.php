<?php

use Dominasys\FilamentLocation\Services\AddressSources\IbgeAddressDataSource;
use Illuminate\Support\Facades\Http;

it('normalizes ibge payloads into the rich dataset format', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), '/localidades/estados') && ! str_contains($request->url(), '/municipios')) {
            return Http::response([
                [
                    'id' => 35,
                    'sigla' => 'SP',
                    'nome' => 'São Paulo',
                    'regiao' => [
                        'id' => 3,
                        'sigla' => 'SE',
                        'nome' => 'Sudeste',
                    ],
                ],
            ], 200);
        }

        if (str_contains($request->url(), '/localidades/estados/35/municipios')) {
            return Http::response([
                [
                    'id' => 3509502,
                    'nome' => 'Campinas',
                ],
            ], 200);
        }

        throw new RuntimeException('Unexpected request: ' . $request->url());
    });

    $dataset = (new IbgeAddressDataSource)->fetch('BR');

    expect($dataset)
        ->toMatchArray([
            'country_code' => 'BR',
            'country_name' => __('filament-location::location.countries.br.name'),
            'source' => 'ibge',
        ])
        ->and($dataset['states'])
        ->toHaveCount(1)
        ->and($dataset['states'][0])
        ->toMatchArray([
            'value' => 'SP',
            'label' => 'São Paulo',
        ])
        ->and($dataset['states'][0]['cities'][0])
        ->toMatchArray([
            'value' => 'Campinas',
            'label' => 'Campinas',
        ]);
});
