<?php
namespace DpmXbrl\Helper;


use DpmXbrl\Config\Config;
use TCPDF;

class ExtendTCPDF extends TCPDF {

    //Page header
    public function Header() {
        $image_file = Config::setLogoPath();
        $this->Image($image_file, PDF_MARGIN_LEFT, 1, 11, '', 'JPG', '', 'T', false, 200, '', false, false, 0, false, false, false);
        $this->SetFont('dejavusans', 'B', 10, '', true);
        $this->SetFillColor(255);
        $this->SetTextColor(0);
        $this->SetDrawColor(255);
        $this->Cell(260, 11, "Agencija za bankarstvo FBiH", 0, 1, 'L', 1, '', 1);
    }

    // Page footer
    public function Footer() {
        //NAPOMENA: RIJESITI ZA PROVJERU PRAVA NAKON PROVJERE SESIJE
       // if (!isset($_SESSION['username_bhbatis'])) {
      //      header("Location: " . FBA_SERVER);
      //  }
        // Position at 10 mm from bottom
     //   $username = $_GET['username'];
        $this->SetY(-10);
        // Set font
        // Page number
        $this->SetFont('dejavusans', 'B', 7, '', true);
    //    $this->Cell(0, 10, 'Izvještaj pripremio/la : ' . $_SESSION['first_name'].' '. $_SESSION['last_name'], 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->SetFont('dejavusans', '', 7, '', true);
        $this->Cell(0, 10, 'Datum generisanja izvještaja: ' . date('d.m.Y'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        $this->Ln(3);
        $this->SetFont('dejavusans', '', 7, '', true);
        $this->Cell(0, 10, 'Obrazac je kreiran programom Agencije za bankarstvo FBiH  ', 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->Cell(0, 10, 'Stranica  ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function Header_Bank_Report($name_application, $name_report, $name_organization, $jmb, $datum, $repeat_num) {
        // set style for barcode
        $style = array(
            'border' => 1,
            'stretch' => true,
            'position' => 'R',
            'align' => 'R',
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );

        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Banka: ", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization, 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $jmb, 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($datum)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj pon.", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $repeat_num, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_No_BAR($name_application, $name_report, $name_organization, $data, $repeat, $type_report, $reason_repeat, $data_lock, $organization) {

        $style = array(
            'position' => 'R',
            'align' => 'R',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => true,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255),
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );


        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["JMB_$organization"], 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Tip izvještaja", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $type_report, 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj pon.", 1, 0, 'L', 1, '', 1);
        $this->Cell((!($organization == 'MKO')) ? 15 : 50, 0, $repeat, 1, 0, 'L', 0, '', 1);
        if (!($organization == 'MKO')) {
            $this->Cell(20, 0, "Datum slanja", 1, 0, 'L', 1, '', 1);
            $this->Cell(15, 0, ($data_lock == null) ? "" : date("d.m.Y", strtotime($data_lock)), 1, 0, 'L', 0, '', 1);
        }
        $this->Cell(20, 0, "Razlog pon.", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $reason_repeat, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Minimal($name_application, $name_report, $name_organization, $data, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["JMB_$organization"], 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Minimal_All($name_application, $name_report, $name_organization, $data, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Capital($name_application, $name_report, $name_organization, $data, $report, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(75, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(75, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(25, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(25, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["JMB_$organization"], 1, 1, 'L', 0, '', 1);
        $this->Cell(25, 0, "Adresa", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["ADRESA"], 1, 0, 'L', 0, '', 1);
        $this->Cell(25, 0, "Direktor", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["DIREKTOR"], 1, 1, 'L', 0, '', 1);
        $this->Cell(25, 0, "Datum fin. stanja", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(25, 0, "Broj pon.", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $report[0]["BROJ_PON"], 1, 1, 'L', 0, '', 1);
        $this->Cell(25, 0, "Razlog pon.", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $report[0]["RAZLOG_PONAVLJANJA"], 1, 0, 'L', 0, '', 1);
        $this->Cell(25, 0, "Datum slanja", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, ($report[0]["DATUM_ZAKLJUCAVANJA"] == null) ? "" : date("d.m.Y", strtotime($report[0]["DATUM_ZAKLJUCAVANJA"])), 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Ponder($name_application, $name_report, $name_organization, $data, $data2, $count, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["JMB_$organization"], 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Početni datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Krajnji datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data2)), 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj pondera", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Transfer($name_application, $name_report, $name_organization, $data, $data2, $count, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["JMB_$organization"], 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Početni datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Krajnji datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data2)), 1, 1, 'L', 0, '', 1);
    }

    public function Footer_Report_Transfer_All($name_application, $name_report, $name_organization, $data, $data2, $count, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Ln();
        $this->Cell(20, 0, "Početni datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Krajnji datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data2)), 1, 1, 'L', 0, '', 1);
    }

    public function Footer_Report_Transfer_Rezime($name_application, $name_report, $name_organization, $data, $data2, $count, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj $organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 0, 'L', 0, '', 1);
        $this->Ln();
        $this->Cell(20, 0, "Početni datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Krajnji datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data2)), 1, 1, 'L', 0, '', 1);
    }

    public function Footer_Report_Ponder_All($name_application, $name_report, $name_organization, $data, $data2, $count, $count_organization, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj pondera", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Početni datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Krajnji datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data2)), 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj izvještaja", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count_organization, 1, 1, 'L', 0, '', 1);

        $this->Ln();
    }

    public function Footer_Report_Trend($name_application, $name_report, $name_organization, $data, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "JMB", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["JMB_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Trend_All($name_application, $name_report, $name_organization, $data, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);

        $this->Ln();
    }

    public function Footer_Report_Trend_All_PKB($name_application, $name_report, $name_organization, $data, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_Choose($name_application, $name_report, $data, $report, $organization) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 0, 'L', 0, '', 1);
        $this->Ln();
        $this->Ln();
        $this->Cell(100, 0, "Izbor za $organization - a", 1, 0, 'C', 1, '', 1);
        $this->Ln();
        $i = 0;
        foreach ($report as $row) {
            $this->Cell(50, 0, $row["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
            ++$i;
            if ($i >= 2) {
                $this->Ln();
                $i = 0;
            }
        }

        $this->Ln();
    }

    public function Footer_Report_All($name_application, $name_report, $name_organization, $data, $report, $organization) {
        if (is_numeric($report)) {
            $count = $report;
        } else {
            $count = sizeof($report);
        }

        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);

        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj $organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_All_PKM($name_application, $name_report, $name_organization, $data, $report, $organization, $dostavljeni) {
        if (is_numeric($report)) {
            $count = $report;
        } else {
            $count = sizeof($report);
        }

        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);

        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj $organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization sa prigovorima:", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $dostavljeni, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function Footer_Report_FSI_All($name_application, $name_report, $name_organization, $data, $report, $organization) {
        if (is_numeric($report)) {
            $count = $report;
        } else {
            $count = sizeof($report);
        }

        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);

        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(70, 0, "Obrazac: $name_report", 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "$organization - a", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $name_organization[0]["NAZIV_$organization"], 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj prispjelih", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

    public function No_Report($data) {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 0, "Izvještaj za traženi datum " . date("d.m.Y", strtotime($data)) . " ne postoji", 0, 0, 'C', 1, '', 1);
        $this->Ln();
    }

    public function No_Report_JMB($data) {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 0, "Izvještaj za traženi JMB i datum " . date("d.m.Y", strtotime($data)) . " ne postoji", 0, 0, 'C', 1, '', 1);
        $this->Ln();
    }

    public function No_Report_JMB_Trend() {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 0, "Izvještaj za traženi JMB i datume ne postoji", 0, 0, 'C', 1, '', 1);
        $this->Ln();
    }

    public function No_Report_Trend() {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 0, "Izvještaj za tražene datume ne postoji", 0, 0, 'C', 1, '', 1);
        $this->Ln();
    }

    public function No_Report_Period() {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 0, "Izvještaj za traženi period ne postoji", 0, 0, 'C', 1, '', 1);
        $this->Ln();
    }

    public function Signer_Report($signer) {
        $this->SetFont('', 'B', 7);
        foreach ($signer as $row) {

            $this->Cell(100, 3, $row['TC_USER_FIRST_NAME'] . "  " . $row['TC_USER_LAST_NAME'] . " / " . $row['TC_USER_PHONE'], 1, 1, 'C', 1, '', 1);
            $this->Ln(1);
            $this->Cell(100, 3, "Potpis    ( Ime i prezime / tel. br. ovlaštenog lica )", 0, 0, 'C', 0, '', 1);
            $this->Ln(4);
        }
    }

    public function Warning_Report($user_alert, $comm_alert) {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(255, 0, 0);
        $this->SetFont('', 'B', 10);
        $this->Cell(0, 0, "Ovaj izvještaj je označen od  $user_alert kao netačan", 0, 1, 'R', 1, '', 1);
        $this->Ln();
        $this->Cell(0, 0, "NAPOMENA:", 0, 1, 'R', 1, '', 1);
        $this->Ln();
        $this->Cell(0, 0, $comm_alert, 0, 1, 'R', 1, '', 1);
        $this->Ln();
    }

    public function Legend($arr, $title) {
        $this->SetFont('', 'B', 7);
        $this->Cell(70, 0, "Legenda", 1, 1, 'C', 1, '', 1);
        $this->Cell(20, 0, "Šifra", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, "Naziv", 1, 0, 'L', 1, '', 1);
        $this->Ln();

        $i = 0;
        $this->SetFont('', '', 6);
        foreach ($arr as $row) {
            $this->Cell(70, 0, $title[$i], 1, 1, 'L', 1, '', 1);
            foreach ($row as $result) {
                $width = 20;
                foreach ($result as $name) {
                    $this->Cell($width, 0, "$name", 1, 0, 'L', 0, '', 1);
                    $width = 50;
                }
                $this->Ln();
            }
            ++$i;
        }
    }

    public function Exchange($name_application, $count, $data, $count_place) {
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);

        $this->SetFont('', 'B', 8);
        $this->Cell(70, 0, "$name_application", 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Datum", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, date("d.m.Y", strtotime($data)), 1, 1, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj ugovora", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count, 1, 0, 'L', 0, '', 1);
        $this->Cell(20, 0, "Broj mjenjača", 1, 0, 'L', 1, '', 1);
        $this->Cell(50, 0, $count_place, 1, 0, 'L', 0, '', 1);
        $this->Ln();
    }

}

?>