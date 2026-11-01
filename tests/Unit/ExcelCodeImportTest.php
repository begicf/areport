<?php

namespace Tests\Unit;

use App\Support\ExcelCodeImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class ExcelCodeImportTest extends TestCase
{
    public function test_values_are_read_at_row_and_column_code_crossings(): void
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle('Cover');
        $sheet = $book->createSheet()->setTitle('C 01.00');
        $sheet->fromArray([
            ['C 01.00 - Own funds'],
            [null, null, 'Amount', 'Ratio'],
            [null, null, '0010', '0020'],
            ['Own funds', '0010', 1250000.5, 0.125],
            ['Tier 1', '0015', 900000, null],
            ['(note)', null, 'ignored'],
        ]);
        $sheet->getStyle('D4')->getNumberFormat()->setFormatCode('0.00%');
        $file = tempnam(sys_get_temp_dir(), 'xl').'.xlsx';
        (new Xlsx($book))->save($file);

        $r = ExcelCodeImport::forTable($file, 'C_01.00');

        $this->assertSame('C 01.00', $r['sheet']);
        $this->assertSame(['c0010r0010' => '1250000.5', 'c0020r0010' => '12.5', 'c0010r0015' => '900000'], $r['values']);
        @unlink($file);
    }

    public function test_a_sheet_without_codes_gives_nothing(): void
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([['a', 'b'], [1, 2]]);
        $file = tempnam(sys_get_temp_dir(), 'xl').'.xlsx';
        (new Xlsx($book))->save($file);
        $this->assertSame([], ExcelCodeImport::forTable($file, 'C_01.00')['values']);
        @unlink($file);
    }
}
