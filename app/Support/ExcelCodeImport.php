<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Read a table from an Excel template laid out with EBA row and column codes — the EBA
 * annotated templates, AReport's own Excel export, or a bank's template that keeps the
 * codes: the row holding the most column codes (0010, 0020…) and the column holding the
 * most row codes give the grid; the value at each crossing goes to cell c<col>r<row>.
 */
class ExcelCodeImport
{
    /**
     * @return array{values: array<string, string>, sheet: ?string, rows: int, columns: int}
     */
    public static function forTable(string $file, string $tableCode): array
    {
        $book = IOFactory::load($file);
        $sheet = self::pickSheet($book->getAllSheets(), $tableCode) ?? $book->getActiveSheet();
        $grid = $sheet->toArray(null, true, false, false);   // calculated, unformatted values

        $isCode = fn ($v) => is_string($v) || is_int($v) ? (bool) preg_match('/^\d{3,4}$/', trim((string) $v)) : false;
        // The header row: most column codes; the code column: most row codes below it.
        [$headerRow, $best] = [null, 0];
        foreach ($grid as $r => $cells) {
            $n = count(array_filter($cells, $isCode));
            if ($n > $best) {
                [$headerRow, $best] = [$r, $n];
            }
        }
        if ($headerRow === null) {
            return ['values' => [], 'sheet' => $sheet->getTitle(), 'rows' => 0, 'columns' => 0];
        }
        $codeCol = null;
        $bestCol = 0;
        $width = max(array_map('count', $grid));
        for ($c = 0; $c < $width; $c++) {
            $n = 0;
            foreach (array_slice($grid, $headerRow + 1) as $cells) {
                $n += $isCode($cells[$c] ?? null) ? 1 : 0;
            }
            if ($n > $bestCol) {
                [$codeCol, $bestCol] = [$c, $n];
            }
        }
        if ($codeCol === null) {
            return ['values' => [], 'sheet' => $sheet->getTitle(), 'rows' => 0, 'columns' => $best];
        }

        $columns = [];
        foreach ($grid[$headerRow] as $c => $v) {
            if ($c !== $codeCol && $isCode($v)) {
                $columns[$c] = str_pad(trim((string) $v), 4, '0', STR_PAD_LEFT);
            }
        }
        $values = [];
        $rows = 0;
        foreach (array_slice($grid, $headerRow + 1, null, true) as $r => $cells) {
            if (! $isCode($cells[$codeCol] ?? null)) {
                continue;
            }
            $rows++;
            $rc = str_pad(trim((string) $cells[$codeCol]), 4, '0', STR_PAD_LEFT);
            foreach ($columns as $c => $cc) {
                $v = $cells[$c] ?? null;
                if ($v === null || $v === '') {
                    continue;
                }
                $values['c'.$cc.'r'.$rc] = self::asEntered($sheet, $r, $c, $v);
            }
        }

        return ['values' => $values, 'sheet' => $sheet->getTitle(), 'rows' => $rows, 'columns' => count($columns)];
    }

    /** The worksheet named after the table (C 01.00, C_01.00, c0100…), if any. */
    private static function pickSheet(array $sheets, string $tableCode): ?Worksheet
    {
        $want = preg_replace('/[^a-z0-9]/', '', strtolower($tableCode));
        foreach ($sheets as $s) {
            $title = preg_replace('/[^a-z0-9]/', '', strtolower($s->getTitle()));
            if ($want !== '' && ($title === $want || str_starts_with($title, $want))) {
                return $s;
            }
        }

        return null;
    }

    /** What the form expects: numbers as typed, percentages formatted with % as 12.5 (not 0.125). */
    private static function asEntered(Worksheet $sheet, int $r, int $c, $v): string
    {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if (is_float($v) || is_int($v)) {
            $format = $sheet->getCell([$c + 1, $r + 1])->getStyle()->getNumberFormat()->getFormatCode();
            if (str_contains((string) $format, '%')) {
                $v *= 100;
            }

            // Excel stores binary floats (900000 may come back as 900000.0000000001): 8 decimals are plenty.
            return rtrim(rtrim(number_format(round((float) $v, 8), 8, '.', ''), '0'), '.');
        }

        return trim((string) $v);
    }
}
