<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'client' => [
        'driver' => 'session',
        'provider' => 'clients',
    ],
    'driver' => [
        'driver' => 'session',
        'provider' => 'drivers',
    ],


    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        'clients' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Client::class,
        ],
         'drivers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Driver::class,
    ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],

        'clients' => [
            'provider' => 'clients',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
        'drivers' => [
        'provider' => 'drivers',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
