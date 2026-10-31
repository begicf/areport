<?php

namespace Tests\Unit;

use AReportDpmXBRL\Creat\CreateXBRLCsvPackage;
use AReportDpmXBRL\Creat\CreateXBRLFromDB;
use AReportDpmXBRL\Library\FactValue;
use AReportDpmXBRL\Library\Lei;
use AReportDpmXBRL\Library\MetricTypes;
use AReportDpmXBRL\Library\ModuleInfo;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class FilingHelpersTest extends TestCase
{
    public function testFactValueScalesWithoutFloatingPointNoise(): void
    {
        $this->assertSame('1500000', FactValue::forKind('monetary', '1500'));
        $this->assertSame('1234567', FactValue::forKind('monetary', '1234.567'));
        $this->assertSame('-500', FactValue::forKind('monetary', '-0.5'));
        $this->assertSame('1500', FactValue::forKind('monetary', '1500', 1));
        $this->assertSame('1500000000', FactValue::forKind('monetary', '1500', 1000000));
        $this->assertSame('0.125', FactValue::forKind('percentage', '12.5'));
        $this->assertSame('0.003', FactValue::forKind('percentage', '0.3'));
        $this->assertSame('0', FactValue::forKind('monetary', '0'));
        $this->assertSame('4', FactValue::forKind('integer', '4'));
        $this->assertSame('eba_GA:PT', FactValue::forKind(null, 'eba_GA:PT'));
    }

    public function testMetricTypesReadDpm2DictionaryAndFallBackToDpm1Prefixes(): void
    {
        $this->assertSame('monetary', MetricTypes::kind('eba42', 'eba_met:qABJ'));   // DPM 2.0 name, dictionary type
        $this->assertNull(MetricTypes::kind('eba42', 'eba_met_4.0:qEBD'));           // enumeration
        $this->assertSame('percentage', MetricTypes::kind('eba42', 'eba_met:pi_unknown'));
        $this->assertSame('monetary', MetricTypes::kind(null, 'eba_met:mi53'));
    }

    public function testLeiCheckDigits(): void
    {
        $this->assertTrue(Lei::isValid('5493001KJTIIGC8Y1R12'));
        $this->assertTrue(Lei::isValid('hwupkr0mpou8fgxbt394'));
        $this->assertFalse(Lei::isValid('HWUPKR0MPOU8FGXBT395'));
        $this->assertFalse(Lei::isValid('DUMMYLEI123456789012'));
        $this->assertFalse(Lei::isValid('TOO-SHORT'));
    }

    public function testModuleInfoAndEbaPackageName(): void
    {
        $info = ModuleInfo::load('eba42', 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd');

        $this->assertSame('4.1.0', $info['version']);
        $this->assertSame('040100', $info['version_code']);
        $this->assertSame('2026-03-31', $info['from']);
        $this->assertSame('COREP', $info['framework']);
        $this->assertSame('COREPOF', $info['module']);
        $this->assertArrayHasKey('C_07.00', $info['filing_indicators']);
        $this->assertContains('c_07.00.a', $info['filing_indicators']['C_07.00']);

        // Matches the EBA sample: DUMMYLEI123456789012.CON_FR_COREP040100_COREPOF_2026-12-31_20260108130834761
        $this->assertSame(
            'DUMMYLEI123456789012.CON_FR_COREP040100_COREPOF_2026-12-31_20260108130834761',
            ModuleInfo::packageName('DUMMYLEI123456789012.CON', 'fr', $info, '2026-12-31', new \DateTimeImmutable('2026-01-08 13:08:34.761'))
        );
        $this->assertNull(ModuleInfo::packageName('X.CON', '', $info, '2026-12-31'));
    }

    public function testCsvPackageUsesOptionsNameAndNegativeIndicators(): void
    {
        $writer = (new CreateXBRLCsvPackage(
            '2026-12-31',
            'rs:DUMMYLEI123456789012.IND',
            'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd',
            [[
                'table_path' => 'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd',
                'sheetcode' => '000', 'column_code' => null, 'row_code' => null, 'row_index' => 1,
                'metric' => 'eba_met:qABJ',
                'value' => '2.5',
                'context' => [
                    'eba_dim_4.0:qADT' => 'eba_qOR:qx2005',
                    'eba_dim_4.0:qJLJ' => 'eba_qOR:qx2078',
                    'eba_dim_4.0:qLNK' => 'eba_qAP:qx2135',
                ],
                'meta' => ['fact_variable_id' => 109056],
            ]],
            'eba42'
        ))->setOptions([
            'baseCurrency' => 'BAM',
            'monetaryScale' => 1000000,
            'decimalsMonetary' => -4,
            'country' => 'BA',
            'filingIndicators' => ['C_02.00' => false],
        ]);

        $package = $writer->writePackage();
        $this->assertMatchesRegularExpression('/^DUMMYLEI123456789012\.IND_BA_COREP040100_COREPOF_2026-12-31_\d{17}\.zip$/', $package['filename']);

        $zip = new ZipArchive();
        $zip->open($package['path']);
        $read = function (string $suffix) use ($zip) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (str_ends_with($zip->getNameIndex($i), $suffix)) {
                    return $zip->getFromIndex($i);
                }
            }
            return null;
        };
        $root = $zip->getNameIndex(0);
        $table = $read('/reports/c_01.00.csv');
        $parameters = $read('/reports/parameters.csv');
        $indicators = $read('/reports/FilingIndicators.csv');
        $zip->close();
        @unlink($package['path']);

        $this->assertStringStartsWith(pathinfo($package['filename'], PATHINFO_FILENAME).'/', $root);
        $this->assertStringContainsString("dp109056,2500000\n", $table);          // 2.5 million BAM
        $this->assertStringContainsString("entityID,rs:DUMMYLEI123456789012.IND\n", $parameters);
        $this->assertStringContainsString("baseCurrency,iso4217:BAM\n", $parameters);
        $this->assertStringContainsString("decimalsMonetary,-4\n", $parameters);
        $this->assertStringContainsString("C_01.00,true\n", $indicators);
        $this->assertStringContainsString("C_02.00,false\n", $indicators);
    }

    public function testXmlUsesDictionaryTypesCurrencyAndNegativeIndicators(): void
    {
        $writer = (new CreateXBRLFromDB(
            '2026-12-31',
            'rs:DUMMYLEI123456789012.CON',
            'www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/mod/corep_of.xsd',
            [[
                'context' => json_encode([]),
                'metric' => 'eba_met:qABJ',     // DPM 2.0 monetary metric: no "mi" in the name
                'numeric_value' => '2.5',
                'sheetcode' => '000',
                'string_value' => '2.5',
                'cr_code' => 'c0010r0010',
            ]],
            ['www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_01.00/c_01.00.xsd'],
            'eba42'
        ))->setOptions(['baseCurrency' => 'BAM', 'monetaryScale' => 1000, 'filingIndicators' => ['C_02.00' => false]]);

        $document = new DOMDocument();
        $document->loadXML($writer->writeXbrl());
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xbrli', 'http://www.xbrl.org/2003/instance');
        $xpath->registerNamespace('find', 'http://www.eurofiling.info/xbrl/ext/filing-indicators');
        $xpath->registerNamespace('eba_met', 'http://www.eba.europa.eu/xbrl/crr/dict/met');

        $fact = $xpath->query('//*[local-name()="qABJ"]')->item(0);
        $this->assertNotNull($fact);
        $this->assertSame('2500', $fact->textContent);
        $this->assertSame('uBAM', $fact->getAttribute('unitRef'));
        $this->assertSame('-3', $fact->getAttribute('decimals'));
        $this->assertSame('iso4217:BAM', $xpath->evaluate('string(//xbrli:unit[@id="uBAM"]/xbrli:measure)'));
        $this->assertSame('false', $xpath->evaluate('string(//find:filingIndicator[.="C_02.00"]/@filed)'));
        $this->assertSame('', $xpath->evaluate('string(//find:filingIndicator[.="C_01.00"]/@filed)'));
    }
}
