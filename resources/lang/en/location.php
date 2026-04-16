<?php

// translations for Dominasys/FilamentLocation
return [
    'fields' => [
        'postal_code' => [
            'label' => 'Postal code',
            'placeholder' => '00000-000',
        ],
        'state' => [
            'label' => 'State',
            'placeholder' => 'Select a state',
        ],
        'city' => [
            'label' => 'City',
            'placeholder' => 'Select a city',
        ],
        'neighborhood' => [
            'label' => 'Neighborhood',
            'placeholder' => 'Neighborhood',
        ],
        'street' => [
            'label' => 'Street',
            'placeholder' => 'Street',
        ],
        'number' => [
            'label' => 'Number',
            'placeholder' => 'Number',
        ],
        'complement' => [
            'label' => 'Complement',
            'placeholder' => 'Complement',
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
