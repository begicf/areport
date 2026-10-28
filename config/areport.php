<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AReport Edition
    |--------------------------------------------------------------------------
    |
    | The public application ships as the open/core edition. Private Composer
    | packages can switch this value in their deployment environment.
    |
    */

    'edition' => env('AREPORT_EDITION', 'core'),

    /*
    |--------------------------------------------------------------------------
    | Extension Service Providers
    |--------------------------------------------------------------------------
    |
    | Keep commercial or customer-specific code outside this repository. Private
    | Laravel packages may expose service providers here, or through the
    | comma-separated AREPORT_EXTENSION_PROVIDERS environment variable.
    |
    */

    'extension_providers' => array_filter(array_map('trim', explode(',', env('AREPORT_EXTENSION_PROVIDERS', '')))),

    /*
    |--------------------------------------------------------------------------
    | Extension Views
    |--------------------------------------------------------------------------
    |
    | Private packages can provide optional Blade partials. The core layout will
    | include these only when they are configured and available.
    |
    */

    'views' => [
        'authenticated_navigation' => array_filter(array_map('trim', explode(',', env('AREPORT_AUTH_NAVIGATION_VIEWS', '')))),
    ],
];
