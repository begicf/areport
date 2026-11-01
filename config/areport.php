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
    | Local plugins
    |--------------------------------------------------------------------------
    |
    | Private plugins (e.g. AReport Pro) are not Composer dependencies of this
    | public repository: a plugin folder found here (relative to the project root,
    | comma-separated) is loaded from its own composer.json — PSR-4 autoload and
    | Laravel providers. A missing folder is skipped, so the core runs without it.
    |
    */

    'plugin_paths' => array_filter(array_map('trim', explode(',', env('AREPORT_PLUGIN_PATHS', '../areport-pro')))),

    /*
    |--------------------------------------------------------------------------
    | Taxonomy uploads
    |--------------------------------------------------------------------------
    |
    | Whether taxonomy packages (and extension packages) may be uploaded or
    | removed in this installation. Off on a shared demo server.
    |
    */

    'upload' => (bool) env('UPLOAD', false),

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
        'to' => env('AREPORT_CONTACT_TO', 'info@areport.net'),
        'min_seconds' => (int) env('AREPORT_CONTACT_MIN_SECONDS', 4),
        'max_links' => (int) env('AREPORT_CONTACT_MAX_LINKS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Key registers
    |--------------------------------------------------------------------------
    |
    | Before export, every key of an open table that refers to a register table
    | must be identified there. EBA's registers (COREP large exposures, DORA) are
    | built in (App\Support\FilingPreflight::KEY_REGISTERS); add others here, e.g.
    | ['register' => 'B_02.01', 'key' => 'CRZ', 'refs' => ['CRZ']] — table code,
    | the register's key dimension, and the key dimensions that refer to it.
    |
    */

    'key_registers' => [],

    /*
    |--------------------------------------------------------------------------
    | Public demo
    |--------------------------------------------------------------------------
    |
    | On a shared demo server, the instances (and areport-pro entities) of this
    | user are visible to every signed-in user, next to their own. Leave empty
    | in production: each user then sees only what they created.
    |
    */

    'demo' => [
        'shared_user_id' => ((int) env('AREPORT_DEMO_SHARED_USER_ID', 0)) ?: null,
        // Visitors can look, validate and export, but not change the shared workspace
        // (taxonomies, settings, the DPM Architect model) or the shared reports.
        'read_only' => (bool) env('AREPORT_DEMO_READONLY', true),
        // E-mail addresses that may still change everything on the demo server.
        'admins' => array_filter(array_map(fn ($e) => strtolower(trim($e)), explode(',', (string) env('AREPORT_DEMO_ADMINS', '')))),
    ],
];
