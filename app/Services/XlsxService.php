<?php

namespace App\Services;

/**
 * Gerador de arquivos XLSX puro PHP (sem dependências externas).
 * Suporta múltiplas abas, formatação, bordas, cores, larguras de coluna.
 */
class XlsxService
{
    private array $sheets = [];
    private int $currentSheet = 0;

    public function __construct()
    {
        $this->addSheet('Planilha1');
    }

    /**
     * Adiciona uma nova aba e a define como ativa.
     */
    public function addSheet(string $name): int
    {
        $index = count($this->sheets);
        $this->sheets[$index] = [
            'name' => $name,
            'rows' => [],
            'columns' => [],
        ];
        $this->currentSheet = $index;
        return $index;
    }

    /**
     * Define a aba ativa pelo índice.
     */
    public function setActiveSheet(int $index): void
    {
        if (isset($this->sheets[$index])) {
            $this->currentSheet = $index;
        }
    }

    public function setSheetName(string $name): void
    {
        $this->sheets[$this->currentSheet]['name'] = $name;
    }

    /**
     * Adiciona uma linha de dados na aba ativa.
     * $style: 'header', 'bold', 'total', 'normal', 'title'
     */
    public function addRow(array $cells, string $style = 'normal'): int
    {
        $rowIndex = count($this->sheets[$this->currentSheet]['rows']) + 1;
        $this->sheets[$this->currentSheet]['rows'][] = ['cells' => $cells, 'style' => $style];
        return $rowIndex;
    }

    public function addEmptyRow(): int
    {
        return $this->addRow([], 'normal');
    }

    public function setColumnWidths(array $widths): void
    {
        $this->sheets[$this->currentSheet]['columns'] = $widths;
    }

    /**
     * Gera o XLSX e retorna como string binária.
     */
    public function generate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/styles.xml', $this->styles());

        // Shared strings (todas as abas)
        $sharedStrings = $this->buildSharedStrings();
        $zip->addFromString('xl/sharedStrings.xml', $sharedStrings['xml']);

        // Cada aba
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet, $sharedStrings['map']));
        }

        $zip->close();

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * Gera e envia o XLSX diretamente ao navegador.
     */
    public function download(string $filename): void
    {
        $content = $this->generate();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: max-age=0');

        echo $content;
        exit;
    }

    private function contentTypes(): string
    {
        $sheets = '';
        foreach ($this->sheets as $i => $s) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    ' . $sheets . '
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }

    private function workbookRels(): string
    {
        $rels = '';
        foreach ($this->sheets as $i => $s) {
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>' . "\n";
        }
        $next = count($this->sheets) + 1;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    ' . $rels . '
    <Relationship Id="rId' . $next . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId' . ($next + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
    }

    private function workbook(): string
    {
        $sheetsXml = '';
        foreach ($this->sheets as $i => $s) {
            $name = htmlspecialchars($s['name'], ENT_XML1);
            $sheetsXml .= '<sheet name="' . $name . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>' . "\n";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        ' . $sheetsXml . '
    </sheets>
</workbook>';
    }

    /**
     * Estilos:
     * 0 = normal (com borda)
     * 1 = header (fundo escuro, texto branco, bold, borda)
     * 2 = bold (com borda)
     * 3 = total (fundo verde, bold, borda)
     * 4 = title (grande, bold, sem borda)
     * 5 = currency (formato R$, com borda)
     * 6 = total+currency (fundo verde, bold, formato R$, borda)
     */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <numFmts count="1">
        <numFmt numFmtId="164" formatCode="&quot;R$ &quot;#,##0.00"/>
    </numFmts>
    <fonts count="4">
        <font><sz val="10"/><name val="Calibri"/></font>
        <font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
        <font><b/><sz val="10"/><name val="Calibri"/></font>
        <font><b/><sz val="14"/><name val="Calibri"/></font>
    </fonts>
    <fills count="4">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF3A3B4E"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFE8F5E9"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color auto="1"/></left>
            <right style="thin"><color auto="1"/></right>
            <top style="thin"><color auto="1"/></top>
            <bottom style="thin"><color auto="1"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="7">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
        <xf numFmtId="164" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
    </cellXfs>
</styleSheet>';
    }

    private function buildSharedStrings(): array
    {
        $strings = [];
        $map = [];
        $index = 0;

        foreach ($this->sheets as $sheet) {
            foreach ($sheet['rows'] as $row) {
                foreach ($row['cells'] as $cell) {
                    $val = (string) $cell;
                    if (!is_numeric($cell) && $val !== '' && !isset($map[$val])) {
                        $map[$val] = $index;
                        $strings[] = $val;
                        $index++;
                    }
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
        foreach ($strings as $s) {
            $xml .= '<si><t>' . htmlspecialchars($s, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $xml .= '</sst>';

        return ['xml' => $xml, 'map' => $map];
    }

    private function sheetXml(array $sheet, array $stringMap): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Colunas
        if (!empty($sheet['columns'])) {
            $xml .= '<cols>';
            foreach ($sheet['columns'] as $i => $width) {
                $col = $i + 1;
                $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $width . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($sheet['rows'] as $rowIdx => $row) {
            $rowNum = $rowIdx + 1;
            $xml .= '<row r="' . $rowNum . '">';

            foreach ($row['cells'] as $colIdx => $cellVal) {
                $colLetter = $this->colLetter($colIdx);
                $ref = $colLetter . $rowNum;
                $styleId = $this->getStyleId($row['style'], $cellVal);
                $val = (string) $cellVal;

                if ($val === '') {
                    $xml .= '<c r="' . $ref . '" s="' . $styleId . '"/>';
                } elseif (is_numeric($cellVal)) {
                    $xml .= '<c r="' . $ref . '" s="' . $styleId . '"><v>' . $cellVal . '</v></c>';
                } elseif (isset($stringMap[$val])) {
                    $xml .= '<c r="' . $ref . '" t="s" s="' . $styleId . '"><v>' . $stringMap[$val] . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . $styleId . '"><is><t>' . htmlspecialchars($val, ENT_XML1, 'UTF-8') . '</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';
        return $xml;
    }

    private function getStyleId(string $rowStyle, $cellVal): int
    {
        switch ($rowStyle) {
            case 'header': return 1;
            case 'bold': return 2;
            case 'total': return is_numeric($cellVal) && $cellVal !== '' ? 6 : 3;
            case 'title': return 4;
            case 'currency': return 5;
            default: return 0;
        }
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }
        return $letter;
    }
}
