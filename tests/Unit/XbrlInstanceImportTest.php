<?php

namespace Tests\Unit;

use App\Support\XbrlInstanceImport;
use Tests\TestCase;

class XbrlInstanceImportTest extends TestCase
{
    public function testSignatureUsesLocalNamesAndSkipsDefaultMembers(): void
    {
        $fromForm = XbrlInstanceImport::signature('eba_met:qCCB', [
            'eba_dim_4.0:qAHH' => 'eba_qCG:qx2030',
            'eba_dim_4.0:qAAG' => 'eba_qLR:x0',   // default member
            'eba_dim_4.0:qACC' => 'eba_qPR:qx0',  // DPM 4.x default member
            'eba_dim_4.0:qCAA' => 'eba_qAI:qx2017',
        ]);
        $fromInstance = XbrlInstanceImport::signature('eba_met:qCCB', [
            'eba_dim:qCAA' => 'eba_AI:qx2017',
            'eba_dim:qAHH' => 'eba_CG:qx2030',
        ]);

        $this->assertSame('qCCB|qAHH=qx2030|qCAA=qx2017', $fromForm);
        $this->assertSame($fromForm, $fromInstance);
    }

    public function testFillsAnEbaTableFromTheEbaSampleInstance(): void
    {
        $public = storage_path('app/public/');
        $samples = glob($public.'sample_instances_4.2_hotfix/xBRL-XML/*_FP_*.xbrl') ?: [];
        $table = $public.'tax/eba44/www.eba.europa.eu/eu/fr/xbrl/crr/fws/fp/4.2/tab/p_01.01/p_01.01.xsd';
        if (! $samples || ! is_file($table)) {
            $this->markTestSkipped('EBA 4.4 taxonomy and the 4.2 sample instances are not in storage.');
        }

        $result = XbrlInstanceImport::forTable($samples[0], $table, [], 'eba44', 1000);

        $this->assertSame(1004, $result['facts']);
        $this->assertSame(164, $result['matched']);
        // 57 646 EUR in the instance is 57.646 on a form kept in thousands.
        $this->assertSame('57.646', $result['values']['c010r010']);
    }

    public function testFillsTheOpenSheetOfAZAxisTable(): void
    {
        $public = storage_path('app/public/');
        $samples = glob($public.'sample_instances_4.2_hotfix/xBRL-XML/*_COREPOF_*.xbrl') ?: [];
        $table = $public.'tax/eba44/www.eba.europa.eu/eu/fr/xbrl/crr/fws/corep/4.2/tab/c_09.01.a/c_09.01.a.xsd';
        if (! $samples || ! is_file($table)) {
            $this->markTestSkipped('EBA 4.4 taxonomy and the 4.2 sample instances are not in storage.');
        }

        // The sheet option as the form posts it.
        $albania = XbrlInstanceImport::forTable($samples[0], $table, ['eba_dim_3.4:CEG' => 'eba_GA:AL', 'sheet' => 'AL'], 'eba44', 1);
        $austria = XbrlInstanceImport::forTable($samples[0], $table, ['eba_dim_3.4:CEG' => 'eba_GA:AT', 'sheet' => 'AT'], 'eba44', 1);

        $this->assertSame(350, $albania['matched']);
        $this->assertSame(0, $austria['matched']);
    }
}
