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

    /*
    |--------------------------------------------------------------------------
    | Contact form
    |--------------------------------------------------------------------------
    |
    | Where the public contact form's messages are mailed (never shown on the
    | page; messages are also stored in contact_messages), and its spam limits.
    |
    */

    'contact' => [
        'to' => env('AREPORT_CONTACT_TO', env('MAIL_FROM_ADDRESS')),
        'min_seconds' => (int) env('AREPORT_CONTACT_MIN_SECONDS', 4),
        'max_links' => (int) env('AREPORT_CONTACT_MAX_LINKS', 2),
    ],
];
