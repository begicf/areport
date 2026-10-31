<?php

namespace App\Support;

use AReportDpmXBRL\Library\Data;
use AReportDpmXBRL\Library\FactValue;
use AReportDpmXBRL\Library\MetricTypes;
use AReportDpmXBRL\Render;
use DOMDocument;
use DOMXPath;

/**
 * Fill the open table of the reporting form from an xBRL-XML instance: each fact
 * (metric + explicit dimensions) goes to the form cell with the same data point.
 *
 * Values come back the way the form shows them: monetary amounts in the instance's
 * monetary scale (e.g. thousands) and percentages as 12.5 for 0.125. Facts with a
 * typed dimension (open tables / typed sheets) have no fixed cell and are not filled.
 */
class XbrlInstanceImport
{
    /**
     * @param  string  $instanceFile  uploaded .xbrl file
     * @param  string  $tablePath  table schema (…/tab/<code>/<code>.xsd) open on the form
     * @param  array  $sheetDims  z-axis members of the open sheet (dimension => member)
     * @param  string|null  $taxonomyFolder  for the metric types (eba44, …)
     * @return array{values: array<string, string>, facts: int, matched: int, typed: int}
     */
    public static function forTable(string $instanceFile, string $tablePath, array $sheetDims, ?string $taxonomyFolder, int $monetaryScale): array
    {
        $facts = self::facts($instanceFile);
        // Only dimension => member pairs (the sheet option also carries "sheet", "typedMember", …).
        $sheetDims = array_filter($sheetDims, fn ($member, $dimension) => is_string($member) && str_contains((string) $dimension, ':'), ARRAY_FILTER_USE_BOTH);

        $render = new Render(Data::getTax($tablePath));
        $form = $render->renderHtmlForm(['sheets' => '000', 'file' => [], 'ext' => 'DB'], null);

        $values = [];
        foreach (self::cellDims((string) ($form['table'] ?? '')) as $cell => $dims) {
            $metric = (string) ($dims['metric'] ?? '');
            unset($dims['metric'], $dims['__meta'], $dims['typedMember']);
            $signature = self::signature($metric, array_merge($dims, $sheetDims));

            if (! array_key_exists($signature, $facts['values'])) {
                continue;
            }

            $values[$cell] = self::forForm($facts['values'][$signature], MetricTypes::kind($taxonomyFolder, $metric), $monetaryScale);
        }

        return ['values' => $values, 'facts' => $facts['count'], 'matched' => count($values), 'typed' => $facts['typed']];
    }

    /** Signature of a data point: metric and explicit members by local name, defaults (x0) left out. */
    public static function signature(string $metric, array $dims): string
    {
        $local = fn (string $qname) => substr($qname, (int) strrpos($qname, ':') + (str_contains($qname, ':') ? 1 : 0));

        $parts = [];
        foreach ($dims as $dimension => $member) {
            if (! is_string($member)) {
                continue;
            }
            $m = $local($member);
            if ($m === 'x0' || $m === 'qx0') {
                continue; // the default member is never written in a context
            }
            $parts[$local((string) $dimension)] = $m;
        }
        ksort($parts);

        $signature = $local($metric);
        foreach ($parts as $dimension => $member) {
            $signature .= "|$dimension=$member";
        }

        return $signature;
    }

    /** @return array{values: array<string, string>, count: int, typed: int} */
    private static function facts(string $file): array
    {
        $doc = new DOMDocument();
        if (! @$doc->load($file, LIBXML_NONET)) {
            throw new \InvalidArgumentException('The file is not a valid xBRL-XML instance.');
        }
        $xp = new DOMXPath($doc);

        $contexts = [];
        foreach ($xp->query("//*[local-name()='context']") as $context) {
            $dims = [];
            foreach ($xp->query(".//*[local-name()='explicitMember']", $context) as $member) {
                $dims[$member->getAttribute('dimension')] = trim($member->textContent);
            }
            $typed = $xp->query(".//*[local-name()='typedMember']", $context)->length > 0;
            $contexts[$context->getAttribute('id')] = ['dims' => $dims, 'typed' => $typed];
        }

        $values = [];
        $count = $typed = 0;
        foreach ($xp->query('/*/*[@contextRef]') as $fact) {
            $context = $contexts[$fact->getAttribute('contextRef')] ?? null;
            if (! $context || stripos($fact->nodeName, 'filingIndicator') !== false) {
                continue;
            }
            $count++;
            if ($context['typed']) {
                $typed++;
                continue;
            }
            $values[self::signature($fact->nodeName, $context['dims'])] = trim($fact->textContent);
        }

        return ['values' => $values, 'count' => $count, 'typed' => $typed];
    }

    /** cell id => decoded data point dimensions, from the form's "<cell>[dim]" fields. */
    private static function cellDims(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_NONET | LIBXML_NOERROR);

        $cells = [];
        foreach ((new DOMXPath($doc))->query('//input[substring(@name, string-length(@name) - 4) = "[dim]"]') as $input) {
            $cell = substr($input->getAttribute('name'), 0, -5);
            $dims = json_decode($input->getAttribute('value'), true);
            if ($cell !== '' && is_array($dims)) {
                $cells[$cell] = $dims;
            }
        }

        return $cells;
    }

    /** An instance value as the form shows it (scaled amounts, percentages × 100). */
    private static function forForm(string $value, ?string $kind, int $monetaryScale): string
    {
        return match ($kind) {
            'monetary' => FactValue::shift($value, -(int) round(log10(max(1, $monetaryScale)))),
            'percentage' => FactValue::shift($value, 2),
            default => $value,
        };
    }
}
