<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use ZipArchive;

/**
 * A report someone already has — an xBRL-XML instance (.xbrl) or an EBA xBRL-CSV report
 * package (.zip) — read into one shape: the module it reports, the filing parameters
 * and its facts. Dimensions are keyed by local name (the CSV writes "eba_dim_4.0:qAAG",
 * an instance "eba_dim:qAAG"); explicit members keep their QName, typed keys their value.
 */
class ReportFile
{
    /**
     * @param  callable(string): ?string  $locate  official URL → local taxonomy file (null when not there)
     * @return array{module: string, format: string, period: ?string, lei: ?string, consolidation: ?string,
     *               currency: ?string, decimals: array<string, int>, indicators: array<string, bool>,
     *               facts: list<array{concept: string, explicit: array<string, string>, typed: array<string, string>, value: string}>}
     */
    public static function read(string $path, string $name, callable $locate): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match ($ext) {
            'xbrl', 'xml' => self::xml($path),
            'zip' => self::csv($path, $locate),
            default => throw new \InvalidArgumentException('Import an xBRL-XML instance (.xbrl) or an xBRL-CSV report package (.zip).'),
        };
    }

    public static function local(string $qname): string
    {
        $p = strrpos($qname, ':');

        return $p === false ? $qname : substr($qname, $p + 1);
    }

    private static function xml(string $path): array
    {
        $doc = new DOMDocument();
        if (! @$doc->load($path, LIBXML_NONET | LIBXML_PARSEHUGE)) {
            throw new \InvalidArgumentException('The file is not a valid xBRL-XML instance.');
        }
        $xp = new DOMXPath($doc);
        $ref = $xp->query("//*[local-name()='schemaRef']")->item(0);
        $module = $ref ? $ref->getAttributeNS('http://www.w3.org/1999/xlink', 'href') : '';
        if ($module === '') {
            throw new \InvalidArgumentException('The instance does not name its module (no schemaRef).');
        }

        $contexts = [];
        $lei = $period = null;
        foreach ($xp->query("//*[local-name()='context']") as $c) {
            $explicit = $typed = [];
            foreach ($xp->query(".//*[local-name()='explicitMember']", $c) as $m) {
                $explicit[self::local($m->getAttribute('dimension'))] = trim($m->textContent);
            }
            foreach ($xp->query(".//*[local-name()='typedMember']", $c) as $m) {
                $typed[self::local($m->getAttribute('dimension'))] = trim($m->textContent);
            }
            $contexts[$c->getAttribute('id')] = compact('explicit', 'typed');
            $lei ??= trim((string) $xp->query(".//*[local-name()='identifier']", $c)->item(0)?->textContent);
            $period ??= trim((string) $xp->query(".//*[local-name()='instant']", $c)->item(0)?->textContent);
        }

        $units = [];
        foreach ($xp->query("//*[local-name()='unit']") as $u) {
            $units[$u->getAttribute('id')] = self::local(trim($u->textContent));
        }

        $facts = $indicators = [];
        $currency = null;
        $decimals = [];
        foreach ($xp->query('/*/*[@contextRef] | /*/*/*[@contextRef]') as $f) {
            if ($f->localName === 'filingIndicator') {
                $indicators[trim($f->textContent)] = $f->getAttributeNS('http://www.eurofiling.info/xbrl/ext/filing-indicators', 'filed') !== 'false';
                continue;
            }
            $context = $contexts[$f->getAttribute('contextRef')] ?? null;
            if (! $context) {
                continue;
            }
            $unit = $units[$f->getAttribute('unitRef')] ?? null;
            if ($unit && strlen($unit) === 3 && strtoupper($unit) === $unit) {
                $currency ??= $unit;
                $decimals['monetary'] ??= is_numeric($f->getAttribute('decimals')) ? (int) $f->getAttribute('decimals') : null;
            }
            $facts[] = ['concept' => $f->nodeName, 'explicit' => $context['explicit'], 'typed' => $context['typed'], 'value' => trim($f->textContent)];
        }

        return self::result($module, 'xml', $period, $lei, $currency, array_filter($decimals, 'is_int'), $indicators, $facts);
    }

    private static function csv(string $path, callable $locate): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \InvalidArgumentException('The file is not a valid .zip report package.');
        }

        try {
            $reports = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = (string) $zip->getNameIndex($i);
                if (preg_match('#(^|/)reports/report\.json$#', $entry)) {
                    $reports = substr($entry, 0, -strlen('report.json'));
                    break;
                }
            }
            if ($reports === null) {
                throw new \InvalidArgumentException('The package has no reports/report.json — it is not an xBRL-CSV report package.');
            }

            $report = json_decode((string) $zip->getFromName($reports.'report.json'), true) ?: [];
            $moduleJsonUrl = (string) (($report['documentInfo']['extends'] ?? [])[0] ?? '');
            if ($moduleJsonUrl === '') {
                throw new \InvalidArgumentException('report.json does not name its module (documentInfo.extends).');
            }
            $moduleJson = $locate($moduleJsonUrl);
            if (! $moduleJson) {
                throw new \InvalidArgumentException('The module '.$moduleJsonUrl.' is not in an uploaded taxonomy.');
            }
            $meta = json_decode((string) file_get_contents($moduleJson), true) ?: [];

            $params = [];
            foreach (self::rows($zip->getFromName($reports.'parameters.csv')) as $row) {
                $params[$row['name'] ?? ''] = $row['value'] ?? '';
            }
            $indicators = [];
            foreach (self::rows($zip->getFromName($reports.'FilingIndicators.csv')) as $row) {
                $indicators[$row['templateID'] ?? ''] = strtolower((string) ($row['reported'] ?? 'true')) !== 'false';
            }
            unset($indicators['']);

            // template name → its table metadata (the module json "extends" the table jsons)
            $templates = [];
            foreach ($meta['documentInfo']['extends'] ?? [] as $rel) {
                if (! str_contains($rel, '/tab/')) {
                    continue;
                }
                $file = realpath(dirname($moduleJson).'/'.$rel);
                if ($file && is_file($file)) {
                    $table = json_decode((string) file_get_contents($file), true) ?: [];
                    foreach ($table['tableTemplates'] ?? [] as $templateName => $template) {
                        $templates[$templateName] = $template;
                    }
                }
            }

            $facts = [];
            foreach ($meta['tables'] ?? [] as $table) {
                $template = $templates[$table['template'] ?? ''] ?? null;
                $csv = $zip->getFromName($reports.($table['url'] ?? ''));
                if (! $template || $csv === false) {
                    continue;
                }
                array_push($facts, ...self::tableFacts($template, self::rows($csv), $params));
            }
        } finally {
            $zip->close();
        }

        $decimals = [];
        foreach (['monetary', 'percentage', 'integer', 'decimal'] as $kind) {
            if (is_numeric($params['decimals'.ucfirst($kind)] ?? null)) {
                $decimals[$kind] = (int) $params['decimals'.ucfirst($kind)];
            }
        }
        $module = preg_replace('/\.json$/', '.xsd', $moduleJsonUrl);

        return self::result($module, 'csv', $params['refPeriod'] ?? null, $params['entityID'] ?? null,
            isset($params['baseCurrency']) ? self::local($params['baseCurrency']) : null, $decimals, $indicators, $facts);
    }

    /** Facts of one table csv: each row's datapoint gives concept and dimensions, "$column" ones come from the row. */
    private static function tableFacts(array $template, array $rows, array $params): array
    {
        $columns = $template['columns'] ?? [];
        $groups = $columns['datapoint']['propertyGroups'] ?? [];
        $shared = ($template['dimensions'] ?? []) + ($columns['factValue']['dimensions'] ?? []);

        $facts = [];
        foreach ($rows as $row) {
            $dp = $row['datapoint'] ?? '';
            $value = $row['factValue'] ?? '';
            if (! isset($groups[$dp]) || $value === '') {
                continue;
            }
            $explicit = $typed = [];
            $concept = '';
            foreach (($groups[$dp]['dimensions'] ?? []) + $shared as $dimension => $member) {
                if ($dimension === 'unit' || $dimension === 'decimals' || $dimension === 'period' || $dimension === 'entity') {
                    continue;
                }
                if (is_string($member) && str_starts_with($member, '$')) {
                    $name = substr($member, 1);
                    if (! array_key_exists($name, $columns)) {
                        continue;   // a parameter ($baseCurrency …), not a dimension value
                    }
                    $member = (string) ($row[$name] ?? '');
                    if ($member === '') {
                        continue;
                    }
                    if (($columns[$name]['tc:constraints']['type'] ?? '') !== 'xs:QName') {
                        $typed[self::local($dimension)] = $member;
                        continue;
                    }
                }
                if ($dimension === 'concept') {
                    $concept = (string) $member;
                } else {
                    $explicit[self::local($dimension)] = (string) $member;
                }
            }
            if ($concept !== '') {
                $facts[] = ['concept' => $concept, 'explicit' => $explicit, 'typed' => $typed, 'value' => (string) $value];
            }
        }

        return $facts;
    }

    /** CSV text → rows keyed by the header. */
    private static function rows($csv): array
    {
        if (! is_string($csv) || $csv === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        $header = str_getcsv((string) array_shift($lines), ',', '"', '');
        $rows = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $cells = str_getcsv($line, ',', '"', '');
            $rows[] = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''));
        }

        return $rows;
    }

    private static function result(string $module, string $format, ?string $period, ?string $entity, ?string $currency, array $decimals, array $indicators, array $facts): array
    {
        // "rs:LEI.CON", "lei:LEI.IND", "LEI" → LEI + consolidation scope
        $id = preg_replace('/^[a-z]+:/i', '', trim((string) $entity));
        $consolidation = preg_match('/\.(CON|IND)$/i', $id, $m) ? strtoupper($m[1]) : null;
        $lei = $id !== '' ? preg_replace('/\.(CON|IND)$/i', '', $id) : null;

        return compact('module', 'format', 'period', 'lei', 'consolidation', 'currency', 'decimals', 'indicators', 'facts');
    }
}
