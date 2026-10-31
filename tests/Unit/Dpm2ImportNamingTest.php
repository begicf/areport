<?php

namespace Tests\Unit;

use Begicf\AReportPro\Services\Dpm2DatabaseImporter;
use Tests\TestCase;

class Dpm2ImportNamingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(Dpm2DatabaseImporter::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
    }

    public function testReleaseLabelAndSchemaFromEbaFileNames(): void
    {
        $this->assertSame('4.4 (2026-10-06)', Dpm2DatabaseImporter::labelFromFileName('DPM2 Database_v 4_4_20261006.accdb'));
        $this->assertSame('4.2.1', Dpm2DatabaseImporter::labelFromFileName('DPM2 Database_v_4_2_1.zip'));
        $this->assertSame('4.2.1', Dpm2DatabaseImporter::labelFromFileName('DPM2 Database_v 4_2_1.accdb'));

        $this->assertSame('dpm2_4_4_2026_10_06', Dpm2DatabaseImporter::schemaFor('4.4 (2026-10-06)'));
        $this->assertSame('dpm2_4_2_1', Dpm2DatabaseImporter::schemaFor('4.2.1'));

        $this->assertTrue(Dpm2DatabaseImporter::looksLikeVersion('4.2.1'));
        $this->assertFalse(Dpm2DatabaseImporter::looksLikeVersion('db'));   // an upload named db.zip is relabelled from its contents
    }
}
