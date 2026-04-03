<?php

namespace Tests\Unit;

use AReportDpmXBRL\Creat\CreateXBRLCsvPackage;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class XbrlExportWriterTest extends TestCase
{
    public function testCsvWriterUsesDatapointRowsAndNormalizedFilingIndicators(): void
    {
        $writer = new CreateXBRLCsvPackage(
            '2026-12-31',
            'DUMMYLEI123456789012.CON',
            'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd',
            [[
                'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_07.00.a/c_07.00.a.xsd',
                'sheetcode' => '000',
                'column_code' => null,
                'row_code' => null,
                'row_index' => 1,
                'metric' => 'eba_met:qNJH',
                'value' => '91347',
                'context' => [
                    'eba_dim_4.0:qEBB' => 'eba_qEC:qx1',
                ],
                'meta' => [
                    'fact_variable_id' => 3291879,
                ],
            ]],
            'eba42'
        );

        $package = $writer->writePackage();
        $zip = $this->openZip($package['path']);

        $tableCsv = $this->readZipEntryBySuffix($zip, '/reports/c_07.00.a.csv');
        $filingIndicators = $this->readZipEntryBySuffix($zip, '/reports/FilingIndicators.csv');
        $parameters = $this->readZipEntryBySuffix($zip, '/reports/parameters.csv');

        $zip->close();
        @unlink($package['path']);

        $this->assertStringContainsString("datapoint,factValue,qEBB\n", $tableCsv);
        $this->assertStringContainsString("dp3291879,91347,eba_qEC:qx1\n", $tableCsv);
        $this->assertStringContainsString("C_07.00,true\n", $filingIndicators);
        $this->assertStringNotContainsString('C_07.00.a', $filingIndicators);
        $this->assertStringContainsString("decimalsInteger,0\n", $parameters);
        $this->assertStringContainsString("baseCurrency,iso4217:EUR\n", $parameters);
        $this->assertStringContainsString("decimalsMonetary,-3\n", $parameters);
        $this->assertStringContainsString("decimalsPercentage,4\n", $parameters);
        $this->assertStringContainsString("decimalsDecimal,2\n", $parameters);
        $this->assertStringNotContainsString('baseLanguage', $parameters);
    }

    public function testCsvWriterKeepsCorepLeParametersAlignedWithSampleShape(): void
    {
        $writer = new CreateXBRLCsvPackage(
            '2026-12-31',
            'DUMMYLEI123456789012.CON',
            'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_le.xsd',
            [[
                'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_27.00/c_27.00.xsd',
                'sheetcode' => '000',
                'column_code' => null,
                'row_code' => null,
                'row_index' => 1,
                'metric' => 'eba_met_4.0:qEBD',
                'value' => 'eba_qSR:qx2001',
                'context' => [
                    'eba_dim_4.2:qINC' => 'irxvlgvg',
                    'eba_dim_4.2:qNCO' => 'eba_qCO:qx2000',
                ],
                'meta' => [
                    'fact_variable_id' => 5486635,
                ],
            ]],
            'eba42'
        );

        $package = $writer->writePackage();
        $zip = $this->openZip($package['path']);

        $tableCsv = $this->readZipEntryBySuffix($zip, '/reports/c_27.00.csv');
        $parameters = $this->readZipEntryBySuffix($zip, '/reports/parameters.csv');

        $zip->close();
        @unlink($package['path']);

        $this->assertStringContainsString("datapoint,factValue,qINC,qNCO\n", $tableCsv);
        $this->assertStringContainsString("dp5486635,eba_qSR:qx2001,irxvlgvg,eba_qCO:qx2000\n", $tableCsv);

        $lines = array_values(array_filter(array_map('trim', explode("\n", trim($parameters)))));

        $this->assertSame([
            'name,value',
            'entityID,rs:DUMMYLEI123456789012.CON',
            'refPeriod,2026-12-31',
            'baseCurrency,iso4217:EUR',
            'decimalsMonetary,-3',
            'decimalsPercentage,4',
        ], $lines);
    }

    public function testXmlWriterMatchesSampleIdentifierAndContextConventions(): void
    {
        $writer = new CreateXBRLFromDB(
            '2026-12-31',
            'rs:DUMMYLEI123456789012.CON',
            'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd',
            [
                [
                    'context' => json_encode([]),
                    'metric' => 'eba_met:mi_dummy',
                    'numeric_value' => '12',
                    'sheetcode' => 'EUR',
                    'string_value' => '12',
                    'cr_code' => 'c0010r1',
                ],
                [
                    'context' => json_encode(['eba_dim_4.0:qEBB' => 'eba_qEC:qx1']),
                    'metric' => 'eba_met:pi_dummy',
                    'numeric_value' => '91',
                    'sheetcode' => '000',
                    'string_value' => '91',
                    'cr_code' => 'c0020r1',
                ],
                [
                    'context' => json_encode(['eba_dim_4.0:qEBB' => 'eba_qEC:qx0']),
                    'metric' => 'eba_met:ii_dummy',
                    'numeric_value' => '4',
                    'sheetcode' => '000',
                    'string_value' => '4',
                    'cr_code' => 'c0030r1',
                ],
            ],
            [
                'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_07.00.a/c_07.00.a.xsd',
                'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/tab/c_07.00.b/c_07.00.b.xsd',
            ],
            'eba42'
        );

        $xml = $writer->writeXbrl();

        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xbrli', 'http://www.xbrl.org/2003/instance');
        $xpath->registerNamespace('find', 'http://www.eurofiling.info/xbrl/ext/filing-indicators');
        $xpath->registerNamespace('link', 'http://www.xbrl.org/2003/linkbase');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $this->assertSame(
            'http://www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.0/mod/corep_of.xsd',
            $xpath->evaluate('string(/xbrli:xbrl/link:schemaRef/@xlink:href)')
        );
        $this->assertSame(
            'https://eurofiling.info/eu/rs',
            $xpath->evaluate('string(/xbrli:xbrl/xbrli:context[@id="cfi"]/xbrli:entity/xbrli:identifier/@scheme)')
        );
        $this->assertSame(
            'DUMMYLEI123456789012.CON',
            $xpath->evaluate('string(/xbrli:xbrl/xbrli:context[@id="cfi"]/xbrli:entity/xbrli:identifier)')
        );
        $this->assertSame('cfi', $xpath->evaluate('string(/xbrli:xbrl/find:fIndicators/find:filingIndicator/@contextRef)'));
        $this->assertSame('C_07.00', $xpath->evaluate('string(/xbrli:xbrl/find:fIndicators/find:filingIndicator)'));
        $this->assertSame(1, (int) $xpath->evaluate('count(/xbrli:xbrl/find:fIndicators/find:filingIndicator)'));
        $this->assertSame(1, (int) $xpath->evaluate('count(/xbrli:xbrl/xbrli:unit[@id="uPURE"])'));
        $this->assertSame(0, (int) $xpath->evaluate('count(/xbrli:xbrl/xbrli:context[@id="c3"]/xbrli:scenario)'));
        $this->assertSame(1, (int) $xpath->evaluate('count(/xbrli:xbrl/xbrli:context[@id="c2"]/xbrli:scenario)'));
    }

    public function testCsvWriterUsesMetadataBackedOpenTableDimensionsInsteadOfRcHeaders(): void
    {
        $writer = new CreateXBRLCsvPackage(
            '2026-04-02',
            'DUMMYLEI123456789012.CON',
            'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/mod/dora.xsd',
            [
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'ARR-001',
                    'context' => [],
                    'meta' => ['header_code' => '0010'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'LEI123',
                    'context' => [],
                    'meta' => ['header_code' => '0020'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'ICT-001',
                    'context' => [],
                    'meta' => ['header_code' => '0030'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'FUNC-1',
                    'context' => [],
                    'meta' => ['header_code' => '0050'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'eba_TA:S01',
                    'context' => [],
                    'meta' => ['header_code' => '0060'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'eba_GA:BA',
                    'context' => [],
                    'meta' => ['header_code' => '0130'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'eba_GA:HR',
                    'context' => [],
                    'meta' => ['header_code' => '0150'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => null,
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => null,
                    'value' => 'eba_GA:HR',
                    'context' => [],
                    'meta' => ['header_code' => '0160'],
                ],
                [
                    'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/dora/4.0/tab/b_02.02/b_02.02.xsd',
                    'sheetcode' => '000',
                    'column_code' => 'c0070',
                    'row_code' => 'r1',
                    'row_index' => 1,
                    'metric' => 'eba_met:di1431',
                    'value' => '2026-04-02',
                    'context' => [],
                    'meta' => ['fact_variable_id' => 3291598],
                ],
            ],
            'eba42'
        );

        $package = $writer->writePackage();
        $zip = $this->openZip($package['path']);
        $tableCsv = $this->readZipEntryBySuffix($zip, '/reports/b_02.02.csv');
        $zip->close();
        @unlink($package['path']);

        $this->assertStringContainsString("datapoint,factValue,CRZ,qLES,ICT,IOB,TYA,qNNB,qNMP,qNMR\n", $tableCsv);
        $this->assertStringContainsString("dp3291598,2026-04-02,ARR-001,LEI123,ICT-001,FUNC-1,eba_TA:S01,eba_GA:BA,eba_GA:HR,eba_GA:HR\n", $tableCsv);
        $this->assertStringNotContainsString('c0010', $tableCsv);
        $this->assertStringNotContainsString('c0020', $tableCsv);
    }

    private function openZip(string $path): ZipArchive
    {
        $zip = new ZipArchive();
        $result = $zip->open($path);

        $this->assertTrue($result === true, 'Unable to open generated ZIP package.');

        return $zip;
    }

    private function readZipEntryBySuffix(ZipArchive $zip, string $suffix): string
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (!is_string($name) || !str_ends_with($name, $suffix)) {
                continue;
            }

            $content = $zip->getFromIndex($index);

            $this->assertIsString($content, 'Unable to read generated ZIP entry: ' . $suffix);

            return $content;
        }

        $this->fail('ZIP entry not found: ' . $suffix);
    }
}
