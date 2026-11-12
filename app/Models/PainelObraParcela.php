<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PainelObraParcela extends Model
{
    protected static string $table = 'painel_obras_parcelas';

    /**
     * Retorna todas as parcelas de uma obra
     */
    public static function getByObra(int $obraId): array
    {
        return Database::fetchAll(
            "SELECT * FROM painel_obras_parcelas WHERE obra_id = ? ORDER BY numero_parcela ASC",
            [$obraId]
        );
    }

    /**
     * Busca parcela por ID do schedule do Nibo
     */
    public static function findByNiboSchedule(string $scheduleId): ?array
    {
        $row = Database::fetch(
            "SELECT * FROM painel_obras_parcelas WHERE nibo_schedule_id = ?",
            [$scheduleId]
        );
        return $row ?: null;
    }

    /**
     * Atualiza status da parcela com base no Nibo
     */
    public static function syncWithNibo(int $parcelaId, float $valorRecebido, ?string $dataRecebimento): void
    {
        $parcela = self::find($parcelaId);
        if (!$parcela) {
            return;
        }

        $valorPrevisto = (float)$parcela['valor_previsto'];
        
        // Define status baseado no valor recebido
        $status = 'Pendente';
        if ($valorRecebido >= $valorPrevisto) {
            $status = 'Pago';
        } elseif ($valorRecebido > 0) {
            $status = 'Parcial';
        }

        Database::query(
            "UPDATE painel_obras_parcelas 
             SET valor_recebido = ?, data_recebimento = ?, status = ?, updated_at = NOW() 
             WHERE id = ?",
            [$valorRecebido, $dataRecebimento, $status, $parcelaId]
        );
    }

    /**
     * Cria ou atualiza parcelas de uma obra
     */
    public static function upsertParcelas(int $obraId, array $parcelas, int $userId): void
    {
        // Remove parcelas existentes
        Database::query("DELETE FROM painel_obras_parcelas WHERE obra_id = ?", [$obraId]);

        // Insere novas parcelas
        foreach ($parcelas as $parcela) {
            Database::query(
                "INSERT INTO painel_obras_parcelas 
                 (obra_id, numero_parcela, valor_previsto, data_vencimento, descricao, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $obraId,
                    $parcela['numero'],
                    $parcela['valor'],
                    $parcela['vencimento'],
                    $parcela['descricao'] ?? null,
                    $userId
                ]
            );
        }
    }

    /**
     * Resumo das parcelas de uma obra
     */
    public static function resumo(int $obraId): array
    {
        $parcelas = self::getByObra($obraId);
        
        $total = 0;
        $recebido = 0;
        $pendente = 0;
        $pagas = 0;
        $abertas = 0;

        foreach ($parcelas as $p) {
            $total += (float)$p['valor_previsto'];
            $recebido += (float)($p['valor_recebido'] ?? 0);
            
            if ($p['status'] === 'Pago') {
                $pagas++;
            } else {
                $abertas++;
                $pendente += (float)$p['valor_previsto'] - (float)($p['valor_recebido'] ?? 0);
            }
        }

        return [
            'total_parcelas' => count($parcelas),
            'valor_total' => $total,
            'valor_recebido' => $recebido,
            'valor_pendente' => $pendente,
            'parcelas_pagas' => $pagas,
            'parcelas_abertas' => $abertas,
            'parcelas' => $parcelas,
        ];
    }
}
