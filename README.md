# AReport

AReport is a Laravel-based data entry and export application for supervisory reporting taxonomies built with the DPM/XBRL model. It renders taxonomy tables as editable HTML forms, stores facts in a relational database, and exports reporting instances in multiple formats.

The application uses the companion package [`begicf/areport-dpm-xbrl`](https://github.com/begicf/areport-dpm-xbrl) to parse taxonomy files, render tables, and prepare export payloads.

## Current State

The current development branch includes the following notable updates:

- Upgraded the application to Laravel `13.3.0`
- Upgraded the UI stack to Bootstrap `5.3.8`
- Reworked the shared layout for full-width, dense financial-reporting screens
- Standardized labels and UI copy to English
- Added active taxonomy visibility in the navigation and taxonomy management flow
- Improved the module explorer with compact search and expand/collapse-all behavior
- Added support for DPM `1.0` and DPM `2.0` taxonomy workflows
- Fixed taxonomy scoping so the application no longer mixes facts from different active taxonomies
- Restored legacy DPM `1.0` rendering behavior while continuing compatibility work for DPM `2.0` taxonomies
- Improved xBRL XML export and added an xBRL-CSV package export path aligned with local EBA sample packages
- Added regression coverage for export writer behavior

## Main Features

- Web-based XBRL/DPM data entry
- HTML rendering of taxonomy tables
- Import from spreadsheet and structured files
- Export to `.xlsx`, `.pdf`, `.html`, `xBRL-XML`, and `xBRL-CSV`
- Taxonomy upload and active taxonomy switching
- Database-backed storage of reporting facts and instances
- Local development with a symlinked parser package

## Requirements

- PHP `8.3+`
- Composer
- Node.js and npm
- A supported database for Laravel
- PHP ZIP extension

## Local Development Setup

Clone the application:

```bash
git clone https://github.com/begicf/areport.git
cd areport
```

Install dependencies:

```bash
composer update
npm install
```

Create the environment file and generate the app key:

```bash
cp .env.example .env
php artisan key:generate
```

Run the database setup:

```bash
php artisan migrate:fresh --seed
```

Build frontend assets:

```bash
npm run prod
```

Start the application:

```bash
php artisan serve
```

## Local Package Link

This project is configured to use the parser package as a local path repository during development:

```json
"repositories": [
  {
    "type": "path",
    "url": "../areport-dpm-xbrl",
    "options": {
      "symlink": true
    }
  }
]
```

That means changes made in the local `../areport-dpm-xbrl` repository are immediately visible inside this application through `vendor/begicf/areport-dpm-xbrl`.

## Environment Notes

Additional environment variables used by the application:

```dotenv
UPLOAD=1
LEI_CODE=12345678912345678912
```

For large taxonomy uploads, increase PHP limits in `php.ini`:

```ini
max_execution_time = 6000
upload_max_filesize = 4000M
post_max_size = 4000M
max_input_vars = 4000
```

## Taxonomy Notes

- Taxonomy files are typically stored under `storage/app/public/tax`
- The application is being maintained with support for both DPM `1.0` and DPM `2.0` taxonomy structures
- The currently active taxonomy is controlled from the taxonomy management UI
- Recent work focused on keeping DPM `1.0` flows stable while improving DPM `2.0` compatibility

## Testing

Run the backend test suite with:

```bash
php artisan test
```

Useful maintenance commands:

```bash
php artisan view:cache
php artisan route:list
php -l path/to/file.php
```

## Export Notes

The application currently contains two main supervisory export paths:

- Legacy `xBRL-XML` export
- `xBRL-CSV` report package export

Recent export work included:

- normalization of filing indicator handling
- better XML context generation, including scenario members
- datapoint-based CSV generation aligned with local EBA sample instances
- improved parameter generation for CSV report packages

## Related Repository

- Application: <https://github.com/begicf/areport>
- Parser package: <https://github.com/begicf/areport-dpm-xbrl>
