<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Epi;
use App\Models\EpiDelivery;
use App\Models\EpiDeliveryItem;
use App\Models\EpiReplacement;

class EpiController extends Controller
{
    /**
     * Middleware: exige usuário logado (sessão do Auth).
     * Retorna o usuário logado.
     */
    private function requireUser(): array
    {
        if (!\App\Core\Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
        return \App\Core\Auth::user();
    }

    // ===================================================================
    // /cadastro-de-epi  — CRUD do catálogo de EPIs
    // ===================================================================

    public function catalog(): void
    {
        $this->requireUser();
        $this->view('site.epi.catalog', [
            'epis' => Epi::all('category ASC, name ASC'),
            'categories' => Epi::distinctCategories(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function catalogStore(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->redirect('/cadastro-de-epi'); return; }

        $name = trim($this->input('name', ''));
        $category = trim($this->input('category', ''));
        $ca = trim($this->input('ca', ''));
        $minDays = (int) $this->input('min_replacement_days', 0);

        if ($name === '') {
            $this->setFlash('error', 'Informe o nome do EPI.');
            $this->redirect('/cadastro-de-epi');
            return;
        }
        if ($minDays < 0) $minDays = 0;

        Epi::create([
            'name' => $name,
            'category' => $category ?: null,
            'ca' => $ca ?: null,
            'min_replacement_days' => $minDays,
            'active' => 1,
            'created_by' => $user['name'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'EPI cadastrado com sucesso.');
        $this->redirect('/cadastro-de-epi');
    }

    public function catalogUpdate(): void
    {
        $this->requireUser();
        if (!$this->isPost()) { $this->redirect('/cadastro-de-epi'); return; }

        $id = (int) $this->input('id', 0);
        $epi = Epi::find($id);
        if (!$epi) { $this->setFlash('error', 'EPI não encontrado.'); $this->redirect('/cadastro-de-epi'); return; }

        $name = trim($this->input('name', ''));
        $category = trim($this->input('category', ''));
        $ca = trim($this->input('ca', ''));
        $minDays = (int) $this->input('min_replacement_days', 0);
        if ($name === '') { $this->setFlash('error', 'Informe o nome do EPI.'); $this->redirect('/cadastro-de-epi'); return; }
        if ($minDays < 0) $minDays = 0;

        Epi::updateById($id, [
            'name' => $name,
            'category' => $category ?: null,
            'ca' => $ca ?: null,
            'min_replacement_days' => $minDays,
        ]);

        $this->setFlash('success', 'EPI atualizado.');
        $this->redirect('/cadastro-de-epi');
    }

    public function catalogDelete(): void
    {
        $this->requireUser();
        if (!$this->isPost()) { $this->redirect('/cadastro-de-epi'); return; }

        $id = (int) $this->input('id', 0);
        // Soft delete para preservar histórico de entregas
        Epi::updateById($id, ['active' => 0]);
        $this->setFlash('success', 'EPI removido.');
        $this->redirect('/cadastro-de-epi');
    }

    // ===================================================================
    // /registro-de-entrega  — formulário de entrega de EPIs
    // ===================================================================

    public function deliveryForm(): void
    {
        $user = $this->requireUser();
        $this->view('site.epi.delivery', [
            'user' => $user,
            'epis' => Epi::allActive(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function deliveryStore(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->redirect('/registro-de-entrega'); return; }

        $workerName = trim($this->input('worker_name', ''));
        $workerDoc = trim($this->input('worker_document', ''));
        $workerRole = trim($this->input('worker_role', ''));
        $deliveredBy = trim($this->input('delivered_by', '')) ?: ($user['name'] ?? '');
        $confirmed = $this->input('confirmed', '') ? 1 : 0;

        // Itens: arrays paralelos
        $epiIds = $_POST['epi_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        $errors = [];
        if ($workerName === '') $errors[] = 'Nome do colaborador';
        if ($workerDoc === '') $errors[] = 'CPF ou Matrícula';
        if ($workerRole === '') $errors[] = 'Cargo';
        if (empty($epiIds)) $errors[] = 'Ao menos um EPI';
        if (!$confirmed) $errors[] = 'Confirmação da entrega';

        // Evidências obrigatórias
        $selfie = $this->saveDataUrlImage($this->input('selfie_data', ''), 'selfie');
        $episPhoto = $this->saveDataUrlImage($this->input('epis_photo_data', ''), 'epis');
        $signature = $this->saveDataUrlImage($this->input('signature_data', ''), 'signature');

        if (!$selfie) $errors[] = 'Selfie do colaborador';
        if (!$episPhoto) $errors[] = 'Foto do colaborador com os EPIs';
        if (!$signature) $errors[] = 'Assinatura';

        if (!empty($errors)) {
            $this->setFlash('error', 'Preencha os campos obrigatórios: ' . implode(', ', $errors) . '.');
            $this->redirect('/registro-de-entrega');
            return;
        }

        $deliveryId = EpiDelivery::create([
            'worker_name' => $workerName,
            'worker_document' => $workerDoc,
            'worker_role' => $workerRole,
            'delivered_by' => $deliveredBy,
            'delivered_by_id' => $user['id'] ?? null,
            'selfie_path' => $selfie,
            'epis_photo_path' => $episPhoto,
            'signature_path' => $signature,
            'confirmed' => $confirmed,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $now = date('Y-m-d H:i:s');
        foreach ($epiIds as $idx => $epiId) {
            $epi = Epi::find((int) $epiId);
            if (!$epi) continue;
            $qty = (float) ($quantities[$idx] ?? 1);
            if ($qty <= 0) $qty = 1;
            EpiDeliveryItem::create([
                'delivery_id' => $deliveryId,
                'epi_id' => $epi['id'],
                'epi_name' => $epi['name'],
                'ca' => $epi['ca'],
                'quantity' => $qty,
                'min_replacement_days' => $epi['min_replacement_days'],
                'delivered_at' => $now,
                'replaced' => 0,
            ]);
        }

        $this->setFlash('success', "Entrega registrada para {$workerName}.");
        $this->redirect('/registro-de-entrega');
    }

    // ===================================================================
    // /substituicao-de-epi  — solicitação/registro de substituição
    // ===================================================================

    public function replacementForm(): void
    {
        $user = $this->requireUser();
        $this->view('site.epi.replacement', [
            'user' => $user,
            'workers' => EpiDelivery::distinctWorkers(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Retorna (JSON) os EPIs ativos de um colaborador com elegibilidade calculada.
     */
    public function replacementItems(): void
    {
        $this->requireUser();
        $doc = trim($this->input('document', ''));
        if ($doc === '') { $this->json(['items' => []]); return; }

        $items = EpiDeliveryItem::activeForWorker($doc);
        $now = time();
        $out = [];
        foreach ($items as $it) {
            // A elegibilidade conta a partir da última substituição; se nunca
            // houve troca, conta a partir da entrega.
            $referenceDate = !empty($it['last_replaced_at']) ? $it['last_replaced_at'] : $it['delivered_at'];
            $referenceTs = strtotime($referenceDate);
            $daysElapsed = (int) floor(($now - $referenceTs) / 86400);
            $minDays = (int) $it['min_replacement_days'];
            $replacementCount = (int) $it['replacement_count'];
            $out[] = [
                'id' => (int) $it['id'],
                'epi_id' => (int) $it['epi_id'],
                'epi_name' => $it['epi_name'],
                'ca' => $it['ca'],
                'quantity' => (float) $it['quantity'],
                'delivered_at' => $it['delivered_at'],
                'last_replaced_at' => $it['last_replaced_at'],
                'reference_date' => $referenceDate,
                'days_elapsed' => $daysElapsed,
                'min_days' => $minDays,
                'eligible' => $daysElapsed >= $minDays,
                'days_remaining' => max(0, $minDays - $daysElapsed),
                'replacement_count' => $replacementCount,
                'next_sequence' => $replacementCount + 1,
            ];
        }
        $this->json(['items' => $out]);
    }

    public function replacementStore(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido'], 405); return; }

        $itemId = (int) $this->input('delivery_item_id', 0);
        $item = EpiDeliveryItem::find($itemId);
        if (!$item) { $this->json(['error' => 'Item de entrega não encontrado.'], 404); return; }

        // Reconferir elegibilidade no servidor: conta a partir da última troca
        // (ou da entrega, se nunca houve troca).
        $lastReplacedAt = \App\Core\Database::fetch(
            "SELECT MAX(created_at) AS last FROM epi_replacements WHERE delivery_item_id = ?",
            [$item['id']]
        )['last'] ?? null;
        $referenceTs = strtotime($lastReplacedAt ?: $item['delivered_at']);
        $daysElapsed = (int) floor((time() - $referenceTs) / 86400);
        if ($daysElapsed < (int) $item['min_replacement_days']) {
            $this->json(['error' => 'Ainda não é possível substituir este EPI (prazo mínimo não atingido).'], 400);
            return;
        }

        $available = (float) $item['quantity'];
        $qty = (float) $this->input('quantity', $available);
        if ($qty <= 0) $qty = $available;
        if ($qty > $available) $qty = $available;

        $oldPhoto = $this->saveDataUrlImage($this->input('old_item_photo_data', ''), 'replace_old');
        $newPhoto = $this->saveDataUrlImage($this->input('new_delivery_photo_data', ''), 'replace_new');

        if (!$oldPhoto) { $this->json(['error' => 'Foto do material substituído é obrigatória.'], 400); return; }
        if (!$newPhoto) { $this->json(['error' => 'Foto da entrega ao operário é obrigatória.'], 400); return; }

        $delivery = EpiDelivery::find((int) $item['delivery_id']);

        // Número sequencial desta substituição para o mesmo item
        $count = (int) (\App\Core\Database::fetch(
            "SELECT COUNT(*) AS total FROM epi_replacements WHERE delivery_item_id = ?",
            [$item['id']]
        )['total'] ?? 0);
        $sequence = $count + 1;

        EpiReplacement::create([
            'delivery_item_id' => $item['id'],
            'sequence_number' => $sequence,
            'epi_id' => $item['epi_id'],
            'epi_name' => $item['epi_name'],
            'ca' => $item['ca'],
            'quantity' => $qty,
            'worker_name' => $delivery['worker_name'] ?? '',
            'worker_document' => $delivery['worker_document'] ?? '',
            'performed_by' => $user['name'] ?? '',
            'performed_by_id' => $user['id'] ?? null,
            'old_item_photo_path' => $oldPhoto,
            'new_delivery_photo_path' => $newPhoto,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // O item permanece disponível para novas substituições; a contagem de
        // dias é reiniciada a partir desta troca (last_replaced_at).
        $this->json(['success' => true, 'sequence' => $sequence]);
    }

    // ===================================================================
    // /historico-de-epi  — histórico de entregas e substituições
    // ===================================================================

    public function history(): void
    {
        $user = $this->requireUser();

        $deliveries = \App\Core\Database::fetchAll(
            "SELECT d.*, COUNT(i.id) AS item_count
             FROM epi_deliveries d
             LEFT JOIN epi_delivery_items i ON i.delivery_id = d.id
             GROUP BY d.id
             ORDER BY d.created_at DESC
             LIMIT 200"
        );
        // Itens por entrega
        $deliveryItems = [];
        if (!empty($deliveries)) {
            $ids = array_column($deliveries, 'id');
            $in = implode(',', array_fill(0, count($ids), '?'));
            $rows = \App\Core\Database::fetchAll(
                "SELECT * FROM epi_delivery_items WHERE delivery_id IN ($in)",
                $ids
            );
            foreach ($rows as $r) { $deliveryItems[$r['delivery_id']][] = $r; }
        }

        $replacements = \App\Core\Database::fetchAll(
            "SELECT * FROM epi_replacements ORDER BY created_at DESC LIMIT 200"
        );

        $this->view('site.epi.history', [
            'user' => $user,
            'deliveries' => $deliveries,
            'deliveryItems' => $deliveryItems,
            'replacements' => $replacements,
            'flash' => $this->getFlash(),
        ]);
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    /**
     * Salva imagem enviada como data URL (base64) e retorna o caminho público.
     */
    private function saveDataUrlImage(string $dataUrl, string $prefix): ?string
    {
        if (empty($dataUrl) || strpos($dataUrl, 'data:image') !== 0) {
            return null;
        }
        if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $dataUrl, $m)) {
            return null;
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false) return null;

        $uploadDir = ROOT_PATH . '/public/uploads/epis/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, $binary) === false) return null;

        return '/uploads/epis/' . $filename;
    }
}
