# AReport — kontekst za nastavak rada

Stanje na dan 2026-10-31 (popodne). Ovaj fajl se čita na početku nove sesije i ažurira na kraju svake veće cjeline.

## Repozitoriji (/Users/beg/Dev)

| Repo | Grana | Uloga |
|---|---|---|
| `areport` | `master` (github.com/begicf/areport) | Laravel 13 host aplikacija (izvještavanje `/table`, instance, export) |
| `areport-pro` | `feature/dpm-architect` (privatan, symlink composer path repo) | DPM Architect, DPM 2.0 baza, validacija |
| `areport-dpm-xbrl` | `publish` — **uvijek `git push origin publish`** (upstream pogrešno na master) | render/export biblioteka |
| `dpm-xl-service` | `master` (privatni github.com/begicf/dpm-xl-service) | Python FastAPI, port 8077, DPM-XL parse/validate/evaluate |

Detaljna arhitektura Architecta: `areport-pro/docs/DPM_ARCHITECT.md`.

### Pokretanje i testovi
- DPM-XL servis restart:
  ```
  kill $(lsof -ti tcp:8077)
  nohup .venv/bin/python -m uvicorn main:app --app-dir /Users/beg/Dev/dpm-xl-service --host 127.0.0.1 --port 8077 &
  ```
- Python testovi: `cd dpm-xl-service && .venv/bin/python -m pytest -q tests` (15 prolazi).
- PHP testovi: `cd areport && php artisan test` (42 prolazi, SQLite in-memory).
- Login: `test@areport.net` / `test` (user id 1).
- Screenshot: puppeteer `/Users/beg/Dev/mica_pacioli/node_modules/puppeteer`, chromium `/opt/homebrew/bin/chromium`; skripta `/tmp/dpm_preview/shot.js <path> <out>`.
- Alati: `mdbtools`, `sevenzip` (`7zz`), `antlr` (`/opt/homebrew/opt/antlr/antlr-4.13.2-complete.jar`), `psql`.

## Pravila rada (postavio korisnik)
- Raditi autonomno, bez YES/NO potvrda.
- NE DIRATI `/Users/beg/Dev/audit_areport`.
- U `areport` commitati samo vlastite izmijenjene fajlove (selektivno), nikad force-push.
- Commit trailer:
  ```
  Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_01CEUPczMMBv152DUvxBouvp
  ```
- DB tabele: razvojna faza — ako ne odgovaraju, smiju se izbrisati i taksonomije ponovo uvesti.
- Disk ~97% pun (~13 GB slobodno). Brisanje EBA 4.0 (5,9 GB) ponuđeno, **nije potvrđeno — ne brisati**.

## Taksonomije (tabela `taxonomies`)
- id1 EBA 4.0 (`eba40`), id2 EBA 4.2 (`eba42`), **id3 EBA 4.4 (`eba44`) — AKTIVNA** (Full 4.4 Phase 1, 23.10.2026, 7,8 GB, novi frameworki amla i tcb).
- 4.4 uvezeno: `dpm:import`, `dpm:import-dict`, `dpm:import-hier --taxonomy=3` → 22 frameworka, 44 modula, 1.710 tabela; 1.170 dimenzija, 2.042 metrike, 556 hijerarhija.
- `dpm:import-validations` za taksonomiju 3 nije pokrenut (validacija 4.4 ide preko DPM 2.0 baze).
- `/taxonomy/managing` prikazuje frameworke i verzije po taksonomiji (`TaxonomyController::frameworksIn`).

## Revizija
Dokument revizije: https://claude.ai/artifact/DPakMrxWLsFCuqtQpY4WeB
(Claude Docs projekat `6454d315-684d-4952-b013-42af051c9c88`, root node `6e6ae742-1140`, Status enum `d2656ae2-f521`).
Stavke 1–10 i UI: Završeno; Postavke: U radu; stavka 11 (DPM 2.0 model): Otvoreno.

## Završeno

### Sprint 1 — sigurnost podataka
- Vlasništvo `fact_module.user_id` + global scope; isto `dpm_entities.user_id`.
- Instanca adresirana po `instance_id`.
- Save, Ctrl+S, autosave 30 s, upozorenje pri zatvaranju taba.
- `FactTable::storeInstance` čuva „0“, briše ispražnjene ćelije/redove.
- Audit u `fact_audit`; stranica `/table/history`.
- Instance vidljive bez obzira na aktivnu taksonomiju.

### Sprint 2 — spremnost za predaju
- Postavke instance: CON/IND, country, base_currency, monetary_scale, decimals, `filing_indicators`.
- `App\Support\FilingPreflight`; rute `/instance/preflight`, `/instance/settings`.
- Biblioteka: `FactValue` (skaliranje bez floatova), `ModuleInfo` (verzija, from/to, `module_vid`, EBA naziv paketa), `Lei::isValid`, `MetricTypes` (iz `dict/met/met.xsd`).
- Identifikator `rs:LEI.CON`; EBA naziv paketa.
- Negativni filing indicators za sve template modula (Filing Rules v5.9).
- XML zabranjen za referentni datum ≥ 31.03.2026.

### Sprint 3 — UX
- Status instance: `data_changed_at`, `validated_at`, `validation_summary`, `exported_at`; `FactModule::status()`.
- Početna s pločicama po statusu.
- Izvještaj validacije: čitljive reference, `/table/open?id&table&cell`, CSV; tabele bez primijenjenog pravila sakrivene iznad 300.
- Bočna lista tabela `/table/navigator`.
- Importer pravila po verzijama/modulima: `dpm_rule_modules`, `dpm_rule_assertions`; preduslovi, težina po modulu, `ignore-val`, fallback na nulu.
- Servis: legacy transform (`True`→`true`, `endif`, skupovi, `match`), brzi evaluator `dpmxl/fast.py`, ANTLR kao rezerva.

### Popravke DPM Architecta
- `areport-pro/src/Services/GridRenderer.php`: L / obrnuto L za neapstraktne roditelje; `child_order` u metadata ordinate (`children-first` zadano za autorizirane, `parent-first` za EBA).
- Modal: provjere RC koda, padajuća lista `child_order`.
- Kontekstni meniji: `clientX`, meni zaglavlja, „Copy cell reference“, Escape zatvara.
- Pretraga case-insensitive; `?table=` prima i kod tabele.
- Ograničena širina naziva u prikazu Form.

### Sprint 4 — DPM 2.0 (uglavnom završen)
- **Uvoz baze:** `php artisan dpm2:import <zip|accdb>` ili `/pro/dpm2` (chunked upload 8 MB, pozadinski exec).
  - Svaka baza u svoju PG šemu: `dpm2_4_2_1`, `dpm2_4_4_2026_10_06` (registar `dpm2_databases`: id1=4.2.1, id2=4.4).
  - `Dpm2DatabaseImporter`: mdbtools + psql `\copy`; GUID kolone kao text; PK samo indeksirani; labela iz imena .accdb u zipu.
  - `Dpm2Controller`: list, upload, destroy, releaseNotes, compare, modules, tables, table, variable, versions, diff.
  - Blade: route helperi preko `{!! json_encode(route(...)) !!}` (`@json` puca na zarezima).
- **`Dpm2Browser`:** `modules(release)`, `tables(modulevid)`, `layout(tablevid)` (zaglavlja s `parentfirst`/`isabstract`, ćelije s VID, excluded), `variable(vid)`, `versions`, `diff` (uparuje redefinisana zaglavlja/ćelije po kodu).
- **DPM-XL v0.9:** EBA gramatika `dpm-xl-service/grammar/v0.9` → `dpmxl/generated09`; parsira 9.543/9.545 pravila. `dpmxl/eval09.py`: recordsetovi, with/default/interval, agregati, `where` i `group by` preko dimenzija, `failing` elementi. Dijalekt `dpm-xl-0.9`; batch prima `tolerance` i `dims`.
- **PHP validacija:** `InstanceValidator::dpm2DatabaseFor(moduleVid)`, `selectDpm2Rules` (operationscope: isactive, fromsubmissiondate, težina, preduslov `{v_X}`). `TableController::moduleCellValues` vraća skalirane vrijednosti, `tolerance` (0,5·10^-decimals), sheet `s…`, `dims`. Izvještaj: „Rules: EBA DPM 2.0 database …“, „Fails for: …“.
- **Rezultati:** instanca 12 → 271 pravilo, 2 greške, 3 upozorenja (isto kao legacy); EBA uzorak COREP OF → 1.218 pravila, ~20 s prvi prolaz.

### Sprint 5 — Architect na DPM 2.0 + generisanje taksonomije (2026-10-31)
- **Navigacija Taxonomy | DPM 2.0** u Architectu: izbor baze i izdanja, stablo framework → verzija modula → tabele; mreža s data pointima; properties ćelije (dp, metrika, kontekst) i zaglavlja (metrika, dimenzije, key); verzije tabele + poređenje. Deep link `?dpm2=<db>:<tablevid>`.
- **„Use as basis“** (`Dpm2TableAdopter`, `POST /pro/dpm2/databases/{db}/tables/{vid}/adopt`): DPM 2.0 tabela → extension/authored tabela (ordinate s RC, labelom, abstract, metrikom i dimenzijama kao XBRL QName; key zaglavlja s dimenzijom + hijerarhijom + članovima; isključene/nepostojeće ćelije → `dpm_disabled_cells`); `metadata.dpm2`. Framework se predodabire iz DPM 2.0 baze.
- Pravilo: property na key zaglavlju = dimenzija, inače metrika (`Property.IsMetric` nije pouzdan, npr. qCDF).
- **`DpmDictionaryResolver`**: indeks svih `dict/` foldera taksonomije (metrike, verzije dimenzija, domene, članovi, uloge hijerarhija), keš `storage/app/dpm-dict-index/<folder>.json`; `frameworkDimensions(fw)` = namespace dimenzija koje koriste EBA tabele frameworka (npr. COREP → `dim/4.0`), radi kompatibilnosti.
- **`DpmTaxonomyGenerator`**: za authored/extension tabelu piše u `tax/<folder>/<owner host>/eu/fr/xbrl/crr/fws/<fw>/<ver>/tab/<code>/`: `.xsd`, `-rend.xml` (ruleNode / aspectNode; open-row key kolone → y breakdowns), `-def.xml` (hiperkocke tačno nad otvorenim ćelijama, zajedničke liste članova key-a preko targetRole), `-lab-en`, `-lab-codes`, `-lab-<lang>`. Apsolutni URL-ovi na EBA rječnik.
- UI: Generate / Preview (render kroz reporting renderer) / Download zip u Properties tabele. Endpointi `dpm-model.table.generate|generated-zip|generated-preview`.
- **Round trip**: C_01.00, C_06.02, C_07.00.a (adopt iz DPM 2.0 → generisanje → render) identični EBA originalu ćeliju po ćeliju (C_07.00.a: 887 otvorenih, 429 zatvorenih, 28 sheetova).
- Biblioteka: `Normalise::resolveHref` (apsolutni href → lokalni root), naziv tabele s `_EXT` sufiksom.
- Fix: close/open ćelije čitao Response umjesto JSON-a; `destroyTable` briše i zatvorene ćelije; authored mreža prikazuje zatvorene ćelije + meni close/open.

### Sprint 6 — refaktorisanje rječnika (2026-10-31, na zahtjev korisnika)
- Model: hijerarhija pripada domeni (ili metrikama), identifikuje je role URI (verzije koegzistiraju); čvor = član/metrika + roditelj, redoslijed, predznak (±1 iz hier-cal), abstract. Enum metrika ima `domain_id` + `hierarchy_id`. Migracija `2026_10_31_000007`.
- Import: svi `hier-def.xml` uklj. verzionisane → 1.885 hijerarhija (prije 556); članovi preko mem.xsd na koji lokator pokazuje; 272/273 enum metrike vezane.
- `/pro/dictionary` (`DpmDictionaryController`, `dictionary/index.blade.php`): Domains (Members / Hierarchies / Used by), Dimensions (picker domene), Metrics (tip; enumeration → domena + hijerarhija), editor stabla hijerarhije (dodaj članove, pomjeri, uvuci, predznak, abstract; EBA → „Copy as own“). Forme u panelima, EBA read-only. Stari pregled/modal rječnika u Architectu uklonjen; toolbar vodi na novu stranicu.
- Korisnik: baza nema bitnih podataka — slobodno refaktorisati/brisati/re-importovati.

### Nedovršeno (necommitovano u areport-pro)
- Generisanje modula: `DpmModuleGenerator` (mod xsd/pre/lab/json, tab.xsd, fws.xsd, taxonomy package), data pointi po ćeliji (`dpm_data_points.signature`, migracija `000006` već pokrenuta), JSON tabele u `DpmTaxonomyGenerator`, EBA dp u `Dpm2TableAdopter`. Netestirano — nastaviti nakon UI popravki.

### Posljednji commitovi
- `areport-pro` 121db41 (DPM 2.0 u Architectu), 2932a21 (generator taksonomije), 01fd60b (rječnik) — pushano
- `areport` b7017d2 — test faktorizacije hiperkocki — pushano
- `areport-dpm-xbrl` 59f460a — resolveHref — pushano
- `dpm-xl-service` 08a5e33 — `where` / `group by`

## Otvoreno / sljedeći koraci
1. **SLJEDEĆE:** generisanje na nivou modula/frameworka: `mod/<modul>.xsd` (entry point s tableGroup/filing indicators), FBA rječnik (authored dimenzije/članovi/metrike → `dict/`), taxonomy package (`META-INF/catalog.xml`, `taxonomyPackage.xml`), validacijska pravila (`val/`), te prikaz generisane tabele na reporting strani (`/table`).
2. Tipizirana key dimenzija: `resolveTypedDomainRef` u biblioteci traži `typ.xsd` relativno na host tabele; za FBA tabele treba fallback na EBA host.
3. Validacija uzorka u Architectu ne prenosi dimenzije iz CSV-a → `where` pravila na uzorku daju „nedostaju podaci“.
4. Ubrzati ANTLR parsiranje DPM 2.0 pravila (prvi prolaz ~20 s); moguć brzi put za v0.9.
5. Historija izmjena prikazuje šifre članova umjesto labela; nema organizacija/timova (vlasništvo po korisniku); `DpmSetting` je globalan.
6. Testni entitet „Apple Inc.“ još u bazi.
7. Razdjelnici hiljada u poljima i rokovi predaje nisu urađeni.
8. Moguće: Excel s pravilima, kvartalni „small packages“ s deaktivacijama.
