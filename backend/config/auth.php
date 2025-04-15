<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Aquestes opcions defineixen el guard d'autenticació per defecte i el broker de
    | reseteig de contrasenyes per a la teva aplicació. Pots canviar aquests valors segons
    | les teves necessitats, però per a la majoria d'aplicacions aquests són un bon punt de partida.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Aquí defines cada guard d'autenticació per a la teva aplicació.
    | Hem afegit un guard específic per als venedors, que farà servir
    | el provider 'vendors' i el model corresponent.
    |
    | També pots adaptar el driver (per exemple, 'session' o 'sanctum' segons la teva implementació).
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'vendor' => [
            'driver' => 'session',
            'provider' => 'vendors',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Aquí es defineixen els providers que s'utilitzaran per recuperar els usuaris
    | des de la base de dades. Hem definit un provider per a 'users' i un altre per a 'vendors'.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        'vendors' => [
            'driver' => 'eloquent',
            'model' => App\Models\Vendor::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Aquestes opcions especifiquen el comportament de la funcionalitat per al reseteig de
    | contrasenyes incloent la taula utilitzada per emmagatzemar els tokens i el provider que
    | s'invoca per recuperar els usuaris.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Aquí pots definir el nombre de segons abans que expiri la confirmació de la contrasenya.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
