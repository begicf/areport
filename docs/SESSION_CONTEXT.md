# AReport — kontekst za nastavak rada

Stanje na dan 2026-10-31. Ovaj fajl se čita na početku nove sesije i ažurira na kraju svake veće cjeline.

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
- PHP testovi: `cd areport && php artisan test` (38 prolazi, SQLite in-memory).
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

### Posljednji commitovi
- `areport-pro` 1312b8f — poređenje verzija tabele
- `areport` 39d7075 — sheet i dims u vrijednostima validacije
- `dpm-xl-service` 08a5e33 — `where` / `group by`
- `areport-dpm-xbrl` 6a06a71 — `module_vid` u ModuleInfo

## Otvoreno / sljedeći koraci
1. **SLJEDEĆE:** Architect na DPM 2.0 podacima — izbor izdanja i DPM 2.0 tabele u stablu Architecta, kao osnova za autoriranje i generisanje taksonomije (uključujući zatvorene ćelije u definition linkbase).
2. Validacija uzorka u Architectu ne prenosi dimenzije iz CSV-a → `where` pravila na uzorku daju „nedostaju podaci“.
3. Ubrzati ANTLR parsiranje DPM 2.0 pravila (prvi prolaz ~20 s); moguć brzi put za v0.9.
4. Historija izmjena prikazuje šifre članova umjesto labela; nema organizacija/timova (vlasništvo po korisniku); `DpmSetting` je globalan.
5. Testni entitet „Apple Inc.“ još u bazi.
6. Razdjelnici hiljada u poljima i rokovi predaje nisu urađeni.
7. Moguće: Excel s pravilima, kvartalni „small packages“ s deaktivacijama.
