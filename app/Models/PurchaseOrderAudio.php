<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderAudio extends Model
{
    protected static string $table = 'purchase_order_audios';

    // Etapas possíveis
    const STAGE_CREATE = 'create';
    const STAGE_QUOTE = 'quote';
    const STAGE_APPROVAL = 'approval';
    const STAGE_FINANCIAL = 'financial';

    /**
     * Lista todos os áudios de um pedido
     */
    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_audios WHERE order_id = ? ORDER BY created_at ASC",
            [$orderId]
        );
    }

    /**
     * Lista áudios de um pedido filtrado por etapa
     */
    public static function getByOrderAndStage(int $orderId, string $stage): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_audios WHERE order_id = ? AND stage = ? ORDER BY created_at ASC",
            [$orderId, $stage]
        );
    }

    /**
     * Lista áudios agrupados por etapa
     */
    public static function getByOrderGrouped(int $orderId): array
    {
        $audios = self::getByOrder($orderId);
        $grouped = [
            self::STAGE_CREATE => [],
            self::STAGE_QUOTE => [],
            self::STAGE_APPROVAL => [],
            self::STAGE_FINANCIAL => [],
        ];

        foreach ($audios as $audio) {
            $stage = $audio['stage'] ?? self::STAGE_CREATE;
            $grouped[$stage][] = $audio;
        }

        return $grouped;
    }

    /**
     * Registrar um novo áudio
     */
    public static function store(array $data): int
    {
        return self::create([
            'order_id' => $data['order_id'],
            'stage' => $data['stage'],
            'filename' => $data['filename'],
            'original_name' => $data['original_name'] ?? $data['filename'],
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
            'recorded_by_user_id' => $data['recorded_by_user_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Excluir áudio (e o arquivo físico)
     */
    public static function deleteWithFile(int $id): bool
    {
        $audio = self::find($id);
        if (!$audio) return false;

        // Remover arquivo físico
        $filePath = ROOT_PATH . '/public/uploads/orders/audio/' . $audio['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        self::deleteById($id);
        return true;
    }

    /**
     * Nomes das etapas para exibição
     */
    public static function stageLabel(string $stage): string
    {
        return match ($stage) {
            self::STAGE_CREATE => 'Criação do Pedido',
            self::STAGE_QUOTE => 'Cotação',
            self::STAGE_APPROVAL => 'Aprovação',
            self::STAGE_FINANCIAL => 'Financeiro',
            default => 'Outro',
        };
    }
}
