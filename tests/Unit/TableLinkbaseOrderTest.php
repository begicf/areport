<?php

namespace Tests\Unit;

use AReportDpmXBRL\Gen\TableLinkbase;
use Tests\TestCase;

class TableLinkbaseOrderTest extends TestCase
{
    public function test_rows_follow_the_arcs_order_not_the_file_order(): void
    {
        // As in MICA Q_09.01: the arcs stand in the file as 1, 3, 7, 5, 2.
        $arcs = '';
        foreach (['r1' => 1, 'r3' => 3, 'r7' => 7, 'r5' => 5, 'r2' => 2] as $to => $order) {
            $arcs .= '<table:definitionNodeSubtreeArc xlink:type="arc" xlink:arcrole="http://xbrl.org/arcrole/2014/definition-node-subtree" xlink:from="root" xlink:to="'.$to.'" order="'.$order.'"/>';
        }
        $file = tempnam(sys_get_temp_dir(), 'rend').'.xml';
        file_put_contents($file, '<?xml version="1.0"?><link:linkbase xmlns:link="http://www.xbrl.org/2003/linkbase" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:table="http://xbrl.org/2014/table" xmlns:df="http://xbrl.org/2008/filter/dimension" xmlns:formula="http://xbrl.org/2008/formula" xmlns:gen="http://xbrl.org/2008/generic" xmlns:xbrldt="http://xbrl.org/2005/xbrldt">'
            .'<link:genericLink xlink:type="extended" xlink:role="http://www.eba.europa.eu/xbrl/crr/role/fws/x">'.$arcs.'</link:genericLink></link:linkbase>');

        $parsed = (new TableLinkbase($file))->Xbrl;
        @unlink($file);

        $this->assertSame(['r1', 'r2', 'r3', 'r5', 'r7'], array_keys($parsed['definitionNodeSubtreeArc']));
    }
}
