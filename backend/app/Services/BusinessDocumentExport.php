<?php

namespace App\Services;

use App\Models\BusinessDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class BusinessDocumentExport
{
    private function xml(string $s): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function zip(array $parts): string
    {
        abort_unless(class_exists(\ZipArchive::class), 503, 'Enable the PHP zip extension for Word and Excel exports. PDF remains available.');
        $file = tempnam(sys_get_temp_dir(), 'invoice-doc-');
        abort_if($file === false, 503, 'Temporary export storage is unavailable.');
        try {
            $z = new \ZipArchive;
            if ($z->open($file, \ZipArchive::OVERWRITE) !== true) {
                abort(503, 'Unable to create export.');
            }
            foreach ($parts as $name => $body) {
                $z->addFromString($name, $body);
            }
            $z->close();

            return file_get_contents($file);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function download(BusinessDocument $d, string $format): array
    {
        $c = $d->content;
        $forecast = $c['salary'] ? app(BusinessDocumentService::class)->forecast($c['salary']) : null;
        if ($format === 'pdf') {
            return [Pdf::loadView('pdf.business-document', ['content' => $c, 'forecast' => $forecast])->setPaper('a4', $forecast ? 'landscape' : 'portrait')->setOption('isRemoteEnabled', false)->output(), 'application/pdf'];
        }
        if ($format === 'xlsx') {
            return [$this->xlsx($c, $forecast), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        }

        return [$this->docx($c, $forecast), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    }

    private function paragraph(string $text, string $style = 'Normal', bool $keepNext = false): string
    {
        return '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/>'.($keepNext ? '<w:keepNext/>' : '').'</w:pPr><w:r><w:t xml:space="preserve">'.$this->xml($text).'</w:t></w:r></w:p>';
    }

    private function docx(array $c, ?array $f): string
    {
        $body = $this->paragraph($c['title'], 'Title');
        foreach (explode("\n", str_replace("\r", '', $c['body'])) as $line) {
            $body .= $this->paragraph($line);
        }
        if ($f) {
            $money = app(CurrencyService::class);
            $body .= $this->paragraph('Currency '.$f['currency'].' | Annual increase '.$c['salary']['increase'].'%');
            foreach ($f['rows'] as $row) {
                $body .= $this->paragraph($row['role'], 'Heading1');
                foreach ($row['values'] as $i => $v) {
                    $body .= $this->paragraph('Year '.($i + 1).': '.$money->format($v, $f['currency'], $f['digits']));
                }
            }
            $body .= $this->paragraph('Totals across listed roles', 'Heading1');
            foreach ($f['totals'] as $i => $v) {
                $body .= $this->paragraph('Year '.($i + 1).': '.$money->format($v, $f['currency'], $f['digits']), 'Normal', $i < count($f['totals']) - 1);
            }
        }

        return $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/_rels/document.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'word/styles.xml' => '<?xml version="1.0"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="100" w:line="280" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:pPr><w:keepNext/></w:pPr><w:rPr><w:b/><w:color w:val="000000"/><w:sz w:val="36"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:pPr><w:keepNext/></w:pPr><w:rPr><w:b/></w:rPr></w:style></w:styles>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr></w:body></w:document>',
        ]);
    }

    private function xlsx(array $c, array $f): string
    {
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rows = '';
        $text = fn ($ref, $s, $style = 0) => '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml($s).'</t></is></c>';
        $num = fn ($ref, $n, $style = 0, $formula = null) => '<c r="'.$ref.'" s="'.$style.'">'.($formula !== null ? '<f>'.$this->xml($formula).'</f>' : '').'<v>'.$n.'</v></c>';
        $rows .= '<row r="1" ht="30" customHeight="1">'.$text('A1', $c['title'], 1).'</row><row r="2">'.$text('A2', $c['company']['company_name']).'</row>';
        $rows .= '<row r="3">'.$text('A3', 'Annual increase').$num('B3', bcdiv((string) $c['salary']['increase'], '100', 8), 3).$text('C3', 'Currency '.$f['currency']).'</row>';
        $rows .= '<row r="4" ht="32" customHeight="1">'.$text('A4', 'Amounts per role per pay period; totals assume one employee per role. Edit blue inputs.').'</row>';
        $rows .= '<row r="5">'.$text('A5', 'Role', 1);
        for ($y = 0; $y < count($f['totals']); $y++) {
            $rows .= $text(chr(66 + $y).'5', 'Year '.($y + 1), 1);
        }
        $rows .= '</row>';
        foreach ($f['rows'] as $i => $row) {
            $n = $i + 6;
            $rows .= '<row r="'.$n.'">'.$text('A'.$n, $row['role'], 4);
            foreach ($row['values'] as $y => $v) {
                $col = chr(66 + $y);
                $prev = chr(65 + $y);
                $rows .= $num($col.$n, bcdiv((string) $v, (string) (10 ** $f['digits']), $f['digits']), $y ? 2 : 5, $y ? 'ROUND('.$prev.$n.'*(1+$B$3),'.$f['digits'].')' : null);
            }
            $rows .= '</row>';
        }
        $n = count($f['rows']) + 6;
        $rows .= '<row r="'.$n.'">'.$text('A'.$n, 'Total', 1);
        foreach ($f['totals'] as $y => $v) {
            $col = chr(66 + $y);
            $rows .= $num($col.$n, bcdiv((string) $v, (string) (10 ** $f['digits']), $f['digits']), 2, 'SUM('.$col.'6:'.$col.($n - 1).')');
        }
        $rows .= '</row>';
        $format = '#,##0'.($f['digits'] ? '.'.str_repeat('0', $f['digits']) : '');

        return $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="'.$ns.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Salary structure" sheetId="1" r:id="rId1"/></sheets><calcPr calcId="0" fullCalcOnLoad="1"/></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0"?><styleSheet xmlns="'.$ns.'"><numFmts count="1"><numFmt numFmtId="164" formatCode="'.$format.'"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><color rgb="FF1954A6"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="6"><xf/><xf fontId="1"/><xf numFmtId="164" applyNumberFormat="1"/><xf fontId="2" numFmtId="10" applyNumberFormat="1"/><xf fontId="2"/><xf fontId="2" numFmtId="164" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0"?><worksheet xmlns="'.$ns.'"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane xSplit="1" ySplit="5" topLeftCell="B6" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="34" customWidth="1"/><col min="2" max="11" width="18" customWidth="1"/></cols><sheetData>'.$rows.'</sheetData><mergeCells count="3"><mergeCell ref="A1:K1"/><mergeCell ref="A2:K2"/><mergeCell ref="A4:K4"/></mergeCells><pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="8" fitToWidth="1" fitToHeight="0"/></worksheet>',
        ]);
    }
}
