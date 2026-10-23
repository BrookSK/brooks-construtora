<?php

namespace App\Services;

use App\Core\Database;

/**
 * Exportação completa (todas as colunas) de tabelas para XLSX.
 *
 * As colunas são descobertas dinamicamente a partir do resultado da query,
 * então qualquer coluna adicionada por migrations futuras entra
 * automaticamente na planilha. Colunas de token de acesso público são
 * omitidas por segurança.
 */
class FullExportService
{
    private XlsxService $xlsx;
    private bool $firstSheet = true;

    /** Colunas omitidas (tokens que dão acesso a links públicos). */
    private array $excludedColumns = ['quote_token', 'approval_token', 'delivery_token'];

    public function __construct()
    {
        $this->xlsx = new XlsxService();
        $this->xlsx->setStrictTypes(true);
    }

    /**
     * Executa as queries em ordem até uma funcionar (fallback para schemas
     * antigos onde alguma tabela/coluna ainda não existe).
     * Retorna null se nenhuma funcionar (tabela inexistente).
     */
    public static function tryFetchAll(array $queries, array $params = []): ?array
    {
        foreach ($queries as $sql) {
            try {
                return Database::fetchAll($sql, $params);
            } catch (\Throwable $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * Adiciona uma aba com todas as linhas/colunas informadas.
     * Se $rows estiver vazio, ainda cria a aba com o cabeçalho de $fallbackHeader.
     */
    public function addSheet(string $name, ?array $rows, array $fallbackHeader = []): void
    {
        if ($rows === null) {
            return; // tabela não existe neste banco
        }

        if ($this->firstSheet) {
            $this->xlsx->setSheetName($name);
            $this->firstSheet = false;
        } else {
            $this->xlsx->addSheet($name);
        }

        $header = !empty($rows) ? array_keys($rows[0]) : $fallbackHeader;
        $header = array_values(array_filter($header, fn($c) => !in_array($c, $this->excludedColumns, true)));

        $this->xlsx->setColumnWidths(array_fill(0, count($header), 18));
        $this->xlsx->addRow($header, 'header');

        foreach ($rows as $row) {
            $cells = [];
            foreach ($header as $col) {
                $cells[] = $this->normalize($row[$col] ?? null);
            }
            $this->xlsx->addRow($cells);
        }
    }

    public function download(string $filename): void
    {
        $this->xlsx->download($filename);
    }

    /**
     * Converte valores do banco para células: números seguros viram número,
     * o resto vira texto limpo (sem caracteres inválidos para XML).
     */
    private function normalize($value)
    {
        if ($value === null) {
            return '';
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $str = (string) $value;
        // Decimais/inteiros sem zero à esquerda e com até 15 dígitos viram número
        if (preg_match('/^-?(0|[1-9]\d{0,14})(\.\d+)?$/', $str)) {
            return strpos($str, '.') !== false ? (float) $str : (int) $str;
        }
        // Remove caracteres de controle inválidos em XML
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $str) ?? $str;
    }
}
