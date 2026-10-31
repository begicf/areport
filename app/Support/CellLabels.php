<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Human names for what the audit trail stores as codes: a cell (c0010r0020 →
 * "Common equity Tier 1 capital · Amount"), a sheet and a member value
 * (eba_qAP:qx2133 → "Other than tangible assets"). Read from the DPM model of the
 * areport-pro plugin; without it every code is returned as it is (null labels).
 */
class CellLabels
{
    private array $tables = [];
    private array $members = [];

    public function __construct(private int $taxonomyId)
    {
    }

    public static function available(): bool
    {
        return class_exists(\Begicf\AReportPro\Models\Dpm\DpmTable::class);
    }

    /** "row label · column label" for a cell of a table, or null. */
    public function cell(string $tablePath, string $crCode): ?string
    {
        if (! preg_match('/^c([0-9a-z._-]+?)r([0-9a-z._-]+)$/i', $crCode, $m)) {
            return null;
        }
        $axes = $this->axes($tablePath);
        $row = $axes['y'][$m[2]] ?? (ctype_digit($m[2]) && strlen($m[2]) < 4 ? 'row '.(int) $m[2] : null);
        $col = $axes['x'][$m[1]] ?? null;

        return ($row || $col) ? implode(' · ', array_filter([$row, $col])) : null;
    }

    /** A sheet's name: its member's label, a typed key's value, or null. */
    public function sheet(string $tablePath, ?string $code): ?string
    {
        if (! $code || $code === '000') {
            return null;
        }
        if (str_starts_with($code, 'k:')) {
            return substr($code, 2);
        }

        return $this->axes($tablePath)['z'][$code] ?? $this->value($code);
    }

    /** Member labels for a QName value (or several, space-separated); null when it is not one. */
    public function value(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^([a-z][\w.-]*:\w+)(\s+[a-z][\w.-]*:\w+)*$/i', $value)) {
            return null;
        }
        $names = [];
        foreach (preg_split('/\s+/', $value) as $qname) {
            $name = $this->member($qname);
            if ($name === null) {
                return null;
            }
            $names[] = $name;
        }

        return implode(', ', $names);
    }

    private function member(string $qname): ?string
    {
        if (array_key_exists($qname, $this->members)) {
            return $this->members[$qname];
        }
        [$prefix, $local] = explode(':', $qname, 2);
        // eba_qAP → domain qAP; fba_FBA_RS → FBA_RS (the prefix is <owner>_<domain>).
        $domain = str_contains($prefix, '_') ? substr($prefix, strpos($prefix, '_') + 1) : $prefix;
        $name = self::available() ? DB::table('dpm_members as m')->join('dpm_domains as d', 'd.id', '=', 'm.domain_id')
            ->where('m.taxonomy_id', $this->taxonomyId)->where('m.code', $local)->where('d.code', $domain)
            ->value('m.name') : null;

        return $this->members[$qname] = $name ?: null;
    }

    /** rc code → label per axis (x, y, z) of the table at $tablePath. */
    private function axes(string $tablePath): array
    {
        $code = strtolower(basename(dirname($tablePath)));
        if (isset($this->tables[$code])) {
            return $this->tables[$code];
        }
        $out = ['x' => [], 'y' => [], 'z' => []];
        if (self::available()) {
            $tableId = DB::table('dpm_tables')->where('taxonomy_id', $this->taxonomyId)->whereRaw('LOWER(code) = ?', [$code])->value('id');
            if ($tableId) {
                $rows = DB::table('dpm_ordinates as o')->join('dpm_axes as a', 'a.id', '=', 'o.axis_id')
                    ->where('a.table_id', $tableId)->whereNotNull('o.rc_code')
                    ->get(['a.orientation', 'o.rc_code', 'o.label']);
                foreach ($rows as $r) {
                    if (isset($out[$r->orientation]) && $r->label !== null && $r->label !== '') {
                        $out[$r->orientation][strtolower($r->rc_code)] = $r->label;
                    }
                }
            }
        }

        return $this->tables[$code] = $out;
    }
}
