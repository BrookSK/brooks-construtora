<?php

namespace App\Services;

/**
 * Gerador de arquivos XLSX puro PHP (sem dependências externas).
 * Gera um XLSX com formatação: cabeçalho, bordas, larguras de coluna, etc.
 */
class XlsxService
{
    private array $rows = [];
    private array $columns = [];
    private array $merges = [];
    private string $sheetName = 'Planilha1';
    private array $styles = [];
    private int $styleCount = 1;

    public function setSheetName(string $name): void
    {
        $this->sheetName = $name;
    }

    /**
     * Adiciona uma linha de dados.
     * $style pode ser: 'header', 'bold', 'total', 'normal', 'title'
     */
    public function addRow(array $cells, string $style = 'normal'): int
    {
        $rowIndex = count($this->rows) + 1;
        $this->rows[] = ['cells' => $cells, 'style' => $style];
        return $rowIndex;
    }

    public function addEmptyRow(): int
    {
        return $this->addRow([], 'normal');
    }

    public function setColumnWidths(array $widths): void
    {
        $this->columns = $widths;
    }

    public function mergeCells(string $range): void
    {
        $this->merges[] = $range;
    }

    /**
     * Gera o arquivo XLSX e retorna como string binária.
     */
    public function generate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        
        // _rels/.rels
        $zip->addFromString('_rels/.rels', $this->rels());
        
        // xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        
        // xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        
        // xl/styles.xml
        $zip->addFromString('xl/styles.xml', $this->styles());
        
        // xl/sharedStrings.xml
        $sharedStrings = $this->buildSharedStrings();
        $zip->addFromString('xl/sharedStrings.xml', $sharedStrings['xml']);
        
        // xl/worksheets/sheet1.xml
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($sharedStrings['map']));

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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
    }

    private function workbook(): string
    {
        $name = htmlspecialchars($this->sheetName, ENT_XML1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="' . $name . '" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
    }

    /**
     * Estilos:
     * 0 = normal
     * 1 = header (fundo escuro, texto branco, bold)
     * 2 = bold
     * 3 = total (fundo verde, bold)
     * 4 = title (grande, bold)
     * 5 = currency (formato R$)
     * 6 = header currency
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
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
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

        foreach ($this->rows as $row) {
            foreach ($row['cells'] as $cell) {
                $val = (string) $cell;
                if (!is_numeric($cell) && !isset($map[$val])) {
                    $map[$val] = $index;
                    $strings[] = $val;
                    $index++;
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

    private function sheet(array $stringMap): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Colunas
        if (!empty($this->columns)) {
            $xml .= '<cols>';
            foreach ($this->columns as $i => $width) {
                $col = $i + 1;
                $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $width . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($this->rows as $rowIdx => $row) {
            $rowNum = $rowIdx + 1;
            $xml .= '<row r="' . $rowNum . '">';

            foreach ($row['cells'] as $colIdx => $cellVal) {
                $colLetter = $this->colLetter($colIdx);
                $ref = $colLetter . $rowNum;
                $styleId = $this->getStyleId($row['style'], $cellVal);
                $val = (string) $cellVal;

                if (is_numeric($cellVal) && $cellVal !== '') {
                    $xml .= '<c r="' . $ref . '" s="' . $styleId . '"><v>' . $cellVal . '</v></c>';
                } else {
                    if (isset($stringMap[$val])) {
                        $xml .= '<c r="' . $ref . '" t="s" s="' . $styleId . '"><v>' . $stringMap[$val] . '</v></c>';
                    } else {
                        $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . $styleId . '"><is><t>' . htmlspecialchars($val, ENT_XML1, 'UTF-8') . '</t></is></c>';
                    }
                }
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        // Merge cells
        if (!empty($this->merges)) {
            $xml .= '<mergeCells count="' . count($this->merges) . '">';
            foreach ($this->merges as $range) {
                $xml .= '<mergeCell ref="' . $range . '"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }

    private function getStyleId(string $rowStyle, $cellVal): int
    {
        // Styles: 0=normal, 1=header, 2=bold, 3=total, 4=title, 5=currency, 6=total+currency
        switch ($rowStyle) {
            case 'header': return 1;
            case 'bold': return 2;
            case 'total': return is_numeric($cellVal) ? 6 : 3;
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
