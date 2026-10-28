# Repository Guidelines

## Project Structure & Module Organization
- `app/` holds the Laravel application code (controllers, models, services).
- `routes/` defines HTTP routes (web and API).
- `resources/` contains Blade views and frontend assets; compiled output is published to `public/`.
- `database/` stores migrations, seeders, and factories.
- `tests/Unit` and `tests/Feature` hold PHPUnit tests.
- `config/`, `bootstrap/`, and `storage/` follow standard Laravel layout for configuration, bootstrapping, and runtime files.

## Build, Test, and Development Commands
- `composer install` (or `composer update`) installs PHP dependencies.
- `php artisan key:generate` initializes the app key after setting up `.env`.
- `php artisan migrate:fresh --seed` recreates the database schema and seeds sample data.
- `php artisan serve` runs the local dev server.
- `npm install` installs frontend dependencies.
- `npm run dev` builds assets for development; `npm run watch` or `npm run hot` runs a watcher; `npm run prod` builds optimized assets.

## Coding Style & Naming Conventions
- Follow `.editorconfig`: 4-space indentation, LF line endings, trim trailing whitespace (except Markdown).
- PHP classes use PascalCase and match file names; keep methods in camelCase.
- Route files and views should use descriptive, domain-focused names (e.g., `routes/web.php`, `resources/views/report/*.blade.php`).

## Testing Guidelines
- PHPUnit is configured in `phpunit.xml` with `tests/Unit` and `tests/Feature`; test files end in `Test.php`.
- Tests run against an in-memory SQLite database by default; keep tests isolated and avoid external dependencies.
- Run tests with `./vendor/bin/phpunit` (or `php artisan test` if preferred).

## Commit & Pull Request Guidelines
- Recent history shows short, descriptive commit subjects (e.g., "update dependencies", "Laravel 12 support"). Use concise, present-tense summaries.
- PRs should include a clear description, steps to verify, and screenshots for UI changes.
- Call out migrations, new environment variables, or changes to `.env.example` in the PR body.

## Configuration & Security Notes
- Copy `.env.example` to `.env` and set required values such as `UPLOAD` and `LEI_CODE`.
- Do not commit secrets; keep credentials in `.env` only.
- Large taxonomy uploads may require PHP configuration updates in `php.ini` (see `README.md`).
