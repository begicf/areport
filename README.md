# AReport

AReport is a web application for supervisory reporting with any taxonomy built on the DPM/XBRL standard (EBA, EIOPA, ECB, national regulators and their extensions). It turns the taxonomy's tables into editable forms, stores the reported facts in a relational database, checks them, and exports filing-ready `xBRL-CSV` and `xBRL-XML` instances.

Taxonomy parsing, table rendering and the instance writers live in the companion package [`begicf/areport-dpm-xbrl`](https://github.com/begicf/areport-dpm-xbrl).

Website: <https://areport.net>

[![AReport — taxonomy-driven reporting, modelled and validated in one workspace](docs/images/landing.png)](https://areport.net)

## Features

### Taxonomies
- Upload taxonomy packages (ZIP) and switch the active taxonomy; every instance and fact is scoped to its taxonomy.
- DPM `1.0` and DPM `2.0` taxonomies (tested with EBA 4.2 and 4.4), including open tables, typed dimensions and owner extensions (e.g. a national regulator's modules next to EBA's).
- The taxonomy page shows the frameworks and module versions each package contains.
- Add an extension package (e.g. a national regulator's taxonomy built on EBA's) to an uploaded taxonomy: its modules appear next to EBA's and resolve offline.
- Module explorer with an owner filter (All / EBA / own), search, and expand or collapse all.

### Reporting
- Instances belong to a user and are addressed by id. Each instance has a reporting entity (LEI and name), a reference date and filing settings.
- Every template renders as an HTML form:
  - Enumerations become dropdowns that follow the hierarchy (indented members, members of other domains).
  - Percentages are entered as %.
  - Open tables add rows with their key.
  - Typed z-axes add sheets by key value (`Add key`), with suggestions of identifiers already used in the instance (e.g. C_27 → C_28/C_29).
- Explicit save, autosave and an audit trail. Large sheets save without hitting PHP's `max_input_vars`.
- Live validation on the form: cells that break a rule are marked red (error) or amber (warning), and a tooltip on the cell shows the rule.
- Instance status and a home dashboard with a status filter.

### Checks and export
- Filing indicators for every template of the module.
- Pre-export checks:
  - open-table rows and typed sheets must have their key;
  - no `xBRL-XML` from reference date 31.03.2026;
  - keys that refer to a register table must be identified there (e.g. COREP large exposures counterparties in C 27.00; DORA entities, contractual arrangements, ICT providers and functions in their registers; more registers can be configured);
  - other filing rules of the reporting framework.
- `xBRL-CSV` report packages and `xBRL-XML` instances following the filing rules (e.g. the EBA filing rules):
  - one context per member set, and one fact per concept and context;
  - shared datapoints reported once;
  - open-table rows carry their keys;
  - typed members are written correctly.
- Export dialog option to check the exported instance with [Arelle](https://arelle.org) (offline, against the local taxonomy copy).
- Export to `.xlsx`, `.pdf` and `.html`, and import from spreadsheets and structured files.

### Workspace
- Reporting entities: add them by LEI, with the legal name and country from the GLEIF register; one is the default for new instances.
- Sign-in requires a verified email address.
- Optional demo mode that shares one user's instances with every signed-in user.
- Public landing page with a spam-protected contact form.

### Private plugin: AReport DPM
The optional `begicf/areport-dpm` package is private. It adds the **validation of reports** against the DPM 2.0 / DPM-XL rules of the framework (EBA and own rules), live on the form and as a full validation report, evaluated by the separate `dpm-xl-service`. It holds the DPM model (frameworks, modules, tables, dictionary, rules) and its importers (`dpm:import*`, `dpm2:import`, `dpm2:operations`).

### Private plugin: AReport Pro
The optional `begicf/areport-pro` package is private and needs AReport DPM. It adds:
- **DPM Architect**: author tables, modules, the data dictionary and validation rules, then generate the XBRL taxonomy (schemas, linkbases, `*-lab-<lang>.xml`). **Export** packs the chosen frameworks as an XBRL taxonomy package, with an optional Arelle check of every module. **Releases** lock a framework version (snapshot, release notes from the changes since the last release, the package as published) and continue in the next version.
- **Label translation**: labels translated in the Architect and the dictionary, with optional AI suggestions from Claude or OpenAI.
- **Settings**: the taxonomy owner and regulator, translation languages and AI translation.

### Private plugin: AReport Teams
The optional `begicf/areport-teams` package is private as well, and works with or without the other plugins. It adds:
- **Teams and roles**: members (invited by e-mail, joining only after they accept) share their reports and entities; each has a role: admin, preparer, reviewer or approver.
- **Sign-off**: a report goes *Draft → In review → Approved* before it is exported for filing. Its data is locked while it is in review or approved. A reviewer returns it with a reason, and someone other than whoever submitted or changed it approves it (four-eyes). Every step is kept with its comment.

AReport works without the plugins; the related screens and actions are simply hidden. Plugins hook into the core through `App\Support\InstanceHooks` (who sees an instance, what locks it, the labels and panels shown with it).

## Requirements

- PHP `8.3+` with the `zip` and `dom` extensions
- Composer, Node.js and npm
- MySQL/MariaDB (or another database supported by Laravel)
- Optional: Arelle (`arelleCmdLine`) for checking exported instances

The application runs on Laravel `13`, Bootstrap `5.3`, PhpSpreadsheet and mPDF.

## Local development

```bash
git clone https://github.com/begicf/areport.git
git clone https://github.com/begicf/areport-dpm-xbrl.git   # sibling folder, symlinked by Composer
cd areport

composer install
npm install && npm run prod

cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

php artisan serve
```

`composer.json` points `begicf/areport-dpm-xbrl` at `../areport-dpm-xbrl` (path repository, symlinked), so changes to the library apply right away.

The private plugins are not Composer dependencies: when their folders sit next to the application (`../areport-dpm`, `../areport-pro`, `../areport-teams`, or the folders listed in `AREPORT_PLUGIN_PATHS`), they are loaded from their own `composer.json` (autoload and Laravel providers). Without them, AReport runs as the open-source core.

## Configuration

The main application variables in `.env`:

| Variable | Purpose |
|---|---|
| `UPLOAD` | `1` allows taxonomy uploads from the UI |
| `LEI_CODE` | fallback LEI when no reporting entity is set |
| `AREPORT_CONTACT_TO` | recipient of contact form messages (default `info@areport.net`) |
| `AREPORT_DEMO_SHARED_USER_ID` | demo mode: whose instances every signed-in user sees (`0` = off) |
| `AREPORT_DEMO_READONLY`, `AREPORT_DEMO_ADMINS` | in demo mode, visitors cannot change taxonomies, settings, the Architect model or the shared reports (default on); admins (e-mails) can |
| `AREPORT_EDITION`, `AREPORT_EXTENSION_PROVIDERS` | edition label and extra service providers |

| `AREPORT_ARELLE_CMD` | path to `arelleCmdLine` (e.g. a venv's `bin/arelleCmdLine`): enables the Arelle check of exports |

With the AReport DPM plugin:

| Variable | Purpose |
|---|---|
| `AREPORT_DPMXL_SERVICE_URL` | DPM-XL service (default `http://127.0.0.1:8077`) |

With the AReport Pro plugin:

| Variable | Purpose |
|---|---|
| `AREPORT_PRO_DPM_OWNER_*`, `AREPORT_PRO_DPM_REGULATOR_*` | owner and regulator of authored taxonomies (also editable under Settings) |
| `AREPORT_PRO_AI_PROVIDER` | AI translation provider: `claude` or `openai` (can also be set under Settings › AI translation) |
| `ANTHROPIC_API_KEY`, `AREPORT_PRO_AI_MODEL` | Claude key and model |
| `OPENAI_API_KEY`, `AREPORT_PRO_AI_OPENAI_MODEL` | OpenAI key and model (e.g. `gpt-5-mini`) |

For large taxonomy uploads, raise the PHP limits in `php.ini`:

```ini
max_execution_time = 6000
upload_max_filesize = 4000M
post_max_size = 4000M
```

Taxonomy packages are unpacked under `storage/app/public/tax/<folder>`.

## Testing

```bash
php artisan test
```

The tests use an in-memory SQLite database. Tests that need a private plugin are skipped when it is not installed.

## Related repositories

- Application: <https://github.com/begicf/areport>
- Taxonomy parser, renderer and instance writers: <https://github.com/begicf/areport-dpm-xbrl>
