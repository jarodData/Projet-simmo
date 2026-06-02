<?php

    // config/auth.php

return [

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'admin' => [
            'driver'   => 'sanctum',
            'provider' => 'admins',
        ],
        'utilisateur' => [
            'driver'   => 'sanctum',
            'provider' => 'utilisateurs',
        ],
        'agent' => [
            'driver'   => 'sanctum',
            'provider' => 'agents',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Administrateur::class,
        ],
        'utilisateurs' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Utilisateur::class,
        ],
        'agents' => [
            'driver' => 'eloquent',
            'model'  => App\Models\AgentImmobilier::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];


