<?php

// translations for Dominasys/FilamentLocation
return [
    'fields' => [
        'postal_code' => [
            'label' => 'Postal code',
        ],
        'state' => [
            'label' => 'State',
        ],
        'city' => [
            'label' => 'City',
        ],
        'neighborhood' => [
            'label' => 'Neighborhood',
        ],
        'street' => [
            'label' => 'Street',
        ],
        'number' => [
            'label' => 'Number',
        ],
        'complement' => [
            'label' => 'Complement',
        ],
    ],

    'countries' => [
        'br' => [
            'name' => 'Brazil',
        ],
    ],

    'notifications' => [
        'invalid' => [
            'title' => 'Invalid postal code',
            'body' => 'The postal code provided is invalid.',
        ],
        'not_found' => [
            'title' => 'Postal code not found',
            'body' => 'We could not find the provided postal code.',
        ],
        'unsupported_country' => [
            'title' => 'Unsupported country',
            'body' => 'The selected country does not have postal code lookup support yet.',
        ],
        'found' => [
            'title' => 'Postal code found',
            'body' => 'Postal code found successfully.',
        ],
    ],
];
