# Commercial Architecture

AReport should be maintained as an open-core Laravel application:

- `begicf/areport` remains the public core application.
- `begicf/areport-dpm-xbrl` remains the public DPM/XBRL parser and export package.
- Commercial features live in one or more private Composer packages.

Do not maintain a second full copy of the application. Shared fixes should land in the public core first, then the commercial package should consume a tagged core release.

## Recommended Repository Layout

```text
areport/                    public core Laravel application
areport-dpm-xbrl/           public parser/export library
areport-pro/                private Laravel package for commercial features
```

The private package should contain only commercial behavior, for example:

- license activation and entitlement checks
- billing or customer account features
- enterprise-only exports and validation flows
- tenant or organization management
- private dashboards, reports, support tools, or branding

## Core Extension Points

The public application exposes extension configuration in `config/areport.php`.

```dotenv
AREPORT_EDITION=pro
AREPORT_EXTENSION_PROVIDERS="Begicf\\AReportPro\\AReportProServiceProvider"
AREPORT_AUTH_NAVIGATION_VIEWS="areport-pro::navigation"
```

`AREPORT_EXTENSION_PROVIDERS` lets the installed private package register routes, migrations, views, commands, policies, and bindings without changing the public core.

`AREPORT_AUTH_NAVIGATION_VIEWS` lets the private package add authenticated navigation items in the existing layout.

## Private Package Skeleton

The private repository can start with this structure:

```text
areport-pro/
  composer.json
  src/AReportProServiceProvider.php
  routes/web.php
  config/areport-pro.php
  resources/views/navigation.blade.php
  resources/views/dashboard.blade.php
  database/migrations/
```

Example private `composer.json`:

```json
{
  "name": "begicf/areport-pro",
  "type": "library",
  "license": "proprietary",
  "autoload": {
    "psr-4": {
      "Begicf\\AReportPro\\": "src/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "Begicf\\AReportPro\\AReportProServiceProvider"
      ]
    }
  },
  "require": {
    "php": "^8.3",
    "illuminate/support": "^13.0"
  }
}
```

Example service provider:

```php
<?php

namespace Begicf\AReportPro;

use Illuminate\Support\ServiceProvider;

class AReportProServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/areport-pro.php', 'areport-pro');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'areport-pro');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

Example `resources/views/navigation.blade.php`:

```blade
<li class="nav-item">
    <a class="nav-link {{ request()->is('pro*') ? 'active' : '' }}" href="{{ url('/pro') }}">
        Pro
    </a>
</li>
```

## Local Development

For local development, add the private package as a path repository in the commercial deployment checkout, not in the public repository:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../areport-pro",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "begicf/areport-pro": "dev-main"
  }
}
```

Then run:

```bash
composer update begicf/areport-pro
php artisan migrate
php artisan route:list
```

## Release Workflow

1. Put shared bug fixes and framework upgrades in `begicf/areport`.
2. Tag a public release, for example `v1.4.0`.
3. Update the commercial deployment to that public tag.
4. Keep Pro-only migrations, routes, controllers, views, and license checks in `begicf/areport-pro`.
5. Run the core test suite and the private package test suite before release.

## Licensing Note

The current license is source-available with a non-commercial restriction. That can support a commercial edition, but it is not an OSI open-source license. If the project must be marketed as open source, use a recognized open-source license and handle commercial value through private add-ons, hosted service terms, trademark, support, or dual licensing.
