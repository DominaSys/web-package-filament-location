<?php

// translations for Dominasys/FilamentLocation
return [
    'fields' => [
        'postal_code' => [
            'label' => 'CEP',
        ],
        'state' => [
            'label' => 'Estado',
        ],
        'city' => [
            'label' => 'Cidade',
        ],
        'neighborhood' => [
            'label' => 'Bairro',
        ],
        'street' => [
            'label' => 'Logradouro',
        ],
        'number' => [
            'label' => 'Número',
        ],
        'complement' => [
            'label' => 'Complemento',
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
