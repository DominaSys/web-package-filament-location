<?php

// translations for Dominasys/FilamentLocation
return [
    'fields' => [
        'postal_code' => [
            'label' => 'CEP',
            'placeholder' => '00000-000',
        ],
        'state' => [
            'label' => 'Estado',
            'placeholder' => 'Selecione um estado',
        ],
        'city' => [
            'label' => 'Cidade',
            'placeholder' => 'Selecione uma cidade',
        ],
        'neighborhood' => [
            'label' => 'Bairro',
            'placeholder' => 'Bairro',
        ],
        'street' => [
            'label' => 'Logradouro',
            'placeholder' => 'Logradouro',
        ],
        'number' => [
            'label' => 'Número',
            'placeholder' => 'Número',
        ],
        'complement' => [
            'label' => 'Complemento',
            'placeholder' => 'Complemento',
        ],
    ],

    'countries' => [
        'br' => [
            'name' => 'Brasil',
        ],
    ],

    'notifications' => [
        'invalid' => [
            'title' => 'CEP inválido',
            'body' => 'O CEP informado é inválido.',
        ],
        'not_found' => [
            'title' => 'CEP não encontrado',
            'body' => 'Não foi possível localizar o CEP informado.',
        ],
        'unsupported_country' => [
            'title' => 'País não suportado',
            'body' => 'O país selecionado ainda não possui suporte a consulta de CEP.',
        ],
        'found' => [
            'title' => 'CEP encontrado',
            'body' => 'CEP localizado com sucesso.',
        ],
    ],
];
