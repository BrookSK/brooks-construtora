<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHistory;
use App\Models\PurchaseOrderAudio;
use App\Models\WeeklyMaterialRequest;
use App\Models\WeeklyMaterialLog;

/**
 * Orquestra a criação de um Pedido real a partir de uma solicitação da
 * Lista Semanal.
 *
 * PRINCÍPIOS (ver prompt):
 *  - Não existe sistema paralelo de pedidos: usa PurchaseOrder::createWithItems.
 *  - Idempotência: uma solicitação concluída gera SOMENTE UM pedido.
 *  - O status 'filled' só é aplicado após confirmar order_id válido.
 */
class WeeklyMaterialService
{
    /**
     * Cria (ou reaproveita) o Pedido de uma solicitação semanal.
     *
     * @param array $request  Registro de weekly_material_requests (com manager_name)
     * @param array $items    Itens do formulário (material_name, quantity, unit, specification, classification, notes)
     * @param array $meta     Campos adicionais: urgency, needed_date, deadline,
     *                        notes, urgency_reason_no_advance,
     *                        urgency_reason_site_occurrence, urgency_description,
     *                        audio_temp_key
     *
     * @return array{success:bool, order_id?:int, order_code?:int, duplicated?:bool, error?:string}
     */
    public static function createOrderFromRequest(array $request, array $items, array $meta = []): array
    {
        $requestId = (int) $request['id'];

        // 1. IDEMPOTÊNCIA — se a solicitação já tem pedido, não cria outro.
        $current = WeeklyMaterialRequest::find($requestId);
        if ($current && !empty($current['order_id'])) {
            $existing = PurchaseOrder::find((int) $current['order_id']);
            if ($existing) {
                return [
                    'success' => true,
                    'duplicated' => true,
                    'order_id' => (int) $existing['id'],
                    'order_code' => $existing['code'],
                ];
            }
        }

        // Idempotência secundária: procura pedido já vinculado à solicitação
        $linked = PurchaseOrder::findByWeeklyRequest($requestId);
        if ($linked) {
            WeeklyMaterialRequest::updateById($requestId, ['order_id' => (int) $linked['id']]);
            return [
                'success' => true,
                'duplicated' => true,
                'order_id' => (int) $linked['id'],
                'order_code' => $linked['code'],
            ];
        }

        // 2. Validar itens
        $validItems = array_values(array_filter($items, fn($it) => !empty(trim($it['material_name'] ?? ''))));
        if (empty($validItems)) {
            return ['success' => false, 'error' => 'Nenhum material informado.'];
        }

        WeeklyMaterialLog::record(
            WeeklyMaterialLog::ACTION_ORDER_CREATE_ATTEMPT,
            $requestId,
            'Tentativa de criação do Pedido a partir da Lista Semanal',
            $request['week_start'] ?? null
        );

        // 3. Montar descrição/observações do pedido
        $descriptionParts = [];
        $descriptionParts[] = 'Origem: Lista Semanal de Materiais (semana '
            . date('d/m/Y', strtotime($request['week_start'])) . ').';
        if (!empty($meta['notes'])) {
            $descriptionParts[] = trim($meta['notes']);
        }

        $urgency = $meta['urgency'] ?? 'medium';
        if (in_array($urgency, ['high', 'critical'])) {
            $reasons = [];
            if (!empty($meta['urgency_reason_no_advance'])) $reasons[] = 'Não houve solicitação antecipada';
            if (!empty($meta['urgency_reason_site_occurrence'])) $reasons[] = 'Ocorrência em obra';
            $descriptionParts[] = 'Urgência ' . ($urgency === 'critical' ? 'Crítica' : 'Alta')
                . '. Motivo(s): ' . implode(' + ', $reasons ?: ['não informado']) . '.';
            if (!empty($meta['urgency_description'])) {
                $descriptionParts[] = 'Justificativa: ' . trim($meta['urgency_description']);
            }
        }

        // deadline (prazo do pedido) = needed_date
        $deadline = !empty($meta['deadline']) ? $meta['deadline'] : ($meta['needed_date'] ?? null);

        // 4. Criar o Pedido pelo PONTO ÚNICO de criação
        try {
            $result = PurchaseOrder::createWithItems([
                'order_type' => 'material',
                'description' => implode("\n", $descriptionParts),
                'urgency' => $urgency,
                'deadline' => $deadline,
                'construction_site_id' => $current['construction_site_id'] ?? ($request['construction_site_id'] ?? null),
                'created_by' => null,
                'created_by_name' => $request['manager_name'] ?? 'Lista Semanal',
                'origin' => 'weekly_list',
                'weekly_request_id' => $requestId,
            ], $validItems);
        } catch (\Throwable $e) {
            error_log('[WEEKLY_MATERIAL] Falha ao criar pedido: ' . $e->getMessage());
            WeeklyMaterialLog::record(
                WeeklyMaterialLog::ACTION_ORDER_FAILED,
                $requestId,
                'Falha ao criar Pedido: ' . $e->getMessage(),
                $request['week_start'] ?? null
            );
            return ['success' => false, 'error' => 'Não foi possível gerar o pedido. Tente novamente.'];
        }

        $orderId = $result['id'];

        // 5. Associar áudio gravado no formulário (mesmo mecanismo do Novo Pedido)
        if (!empty($meta['audio_temp_key'])) {
            try {
                PurchaseOrderAudio::assignTempToOrder($meta['audio_temp_key'], $orderId);
            } catch (\Throwable $e) {}
        }

        // 6. Histórico do pedido (fluxo existente)
        PurchaseOrderHistory::log(
            $orderId,
            'created',
            'Pedido criado via Lista Semanal de Materiais por ' . ($request['manager_name'] ?? 'responsável'),
            $request['manager_name'] ?? 'Lista Semanal',
            null
        );

        // 7. Vincular pedido à solicitação (fonte de controle gerencial)
        WeeklyMaterialRequest::updateById($requestId, ['order_id' => $orderId]);

        WeeklyMaterialLog::record(
            WeeklyMaterialLog::ACTION_ORDER_CREATED,
            $requestId,
            "Pedido {$result['code']} criado com sucesso",
            $request['week_start'] ?? null,
            $orderId
        );

        return [
            'success' => true,
            'duplicated' => false,
            'order_id' => $orderId,
            'order_code' => $result['code'],
            'quote_token' => $result['quote_token'],
        ];
    }
}
