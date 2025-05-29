<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Enable CORS so que el teu front, que corre en un host diferent, pugui
    | fer peticions a la teva API.
    |
    */

    // Quins paths vols exposar per CORS
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Orígens permesos (pots restringir-ho més tard)
    'allowed_origins' => ['http://localhost:5173'],

    // Mètodes HTTP permesos
    'allowed_methods' => ['*'],

    // Headers que acceptes
    'allowed_headers' => ['*'],

    // Si els clients poden rebre credencials (cookies, auth headers)
    'supports_credentials' => true,
];
