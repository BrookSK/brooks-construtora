<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Epi;
use App\Models\EpiDelivery;
use App\Models\EpiDeliveryItem;
use App\Models\EpiReplacement;
use App\Models\EpiReturn;

class EpiController extends Controller
{
    /**
     * Middleware: exige usuário logado (PIN ou Admin) com permissão de EPI.
     * Retorna o usuário logado.
     */
    private function requireUser(): array
    {
        // Primeiro tenta PIN auth (usuários de EPI acessam por PIN)
        $pinUser = PinAuthController::getLoggedUser();
        if ($pinUser) {
            // Permite acesso se a role for 'epi' ou 'all'
            if ($pinUser['role'] !== 'epi' && $pinUser['role'] !== 'all') {
                http_response_code(403);
                echo '<h1>Sem permissão</h1><p>Você não tem acesso à funcionalidade de EPI.</p>';
                exit;
            }
            // Configura sessão para que o layout/sidebar funcione corretamente
            $_SESSION['user_id'] = $pinUser['id'];
            $_SESSION['user_name'] = $pinUser['name'];
            $_SESSION['user_email'] = $pinUser['email'] ?? '';
            $_SESSION['user_role'] = 'epi';
            $_SESSION['pin_auth'] = true;
            $_SESSION['pin_user_id'] = $pinUser['id'];
            $_SESSION['pin_user_role'] = $pinUser['role'];

            return [
                'id' => $pinUser['id'],
                'name' => $pinUser['name'],
                'email' => $pinUser['email'] ?? '',
                'role' => 'epi',
            ];
        }

        // Fallback: admin auth (super_admin/admin sempre pode acessar)
        if (\App\Core\Auth::check()) {
            return \App\Core\Auth::user();
        }

        // Nenhum dos dois → redireciona para login PIN
        $this->redirect('/pin/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/cadastro-de-epi'));
        exit;
    }

    // ===================================================================
    // /cadastro-de-epi  — CRUD do catálogo de EPIs
    // ===================================================================

    public function catalog(): void
    {
        $user = $this->requireUser();
        $this->view('site.epi.catalog', [
            'user' => $user,
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
            'recipientType' => 'worker',
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Distribuição de EPIs para terceiros (mesma tela/lógica da entrega,
     * porém com destinatário não vinculado ao quadro de colaboradores).
     */
    public function thirdPartyForm(): void
    {
        $user = $this->requireUser();
        $this->view('site.epi.delivery', [
            'user' => $user,
            'epis' => Epi::allActive(),
            'recipientType' => 'third_party',
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Busca dinâmica de destinatários já cadastrados (JSON).
     */
    public function searchWorkers(): void
    {
        $this->requireUser();
        $term = trim($this->input('q', ''));
        $recipientType = $this->input('recipient_type', 'worker') === 'third_party' ? 'third_party' : 'worker';
        if (strlen($term) < 1) { $this->json(['workers' => []]); return; }

        $workers = EpiDelivery::searchWorkers($term, $recipientType);
        $this->json(['workers' => array_map(fn($w) => [
            'name' => $w['worker_name'],
            'document' => $w['worker_document'],
            'role' => $w['worker_role'],
        ], $workers)]);
    }

    public function deliveryStore(): void
    {
        $this->processDeliveryStore('worker', '/registro-de-entrega');
    }

    public function thirdPartyStore(): void
    {
        $this->processDeliveryStore('third_party', '/distribuicao-terceiros');
    }

    /**
     * Processa o registro de entrega, reutilizado tanto para colaboradores
     * quanto para terceiros.
     */
    private function processDeliveryStore(string $recipientType, string $redirect): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->redirect($redirect); return; }

        $isThird = $recipientType === 'third_party';
        $workerLabel = $isThird ? 'terceiro' : 'colaborador';

        $workerName = trim($this->input('worker_name', ''));
        $workerDoc = trim($this->input('worker_document', ''));
        $workerRole = trim($this->input('worker_role', ''));
        $deliveredBy = trim($this->input('delivered_by', '')) ?: ($user['name'] ?? '');
        $confirmed = $this->input('confirmed', '') ? 1 : 0;

        // Itens: arrays paralelos
        $epiIds = $_POST['epi_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];

        $errors = [];
        if ($workerName === '') $errors[] = 'Nome do ' . $workerLabel;
        if ($workerDoc === '') $errors[] = 'CPF ou Matrícula';
        if ($workerRole === '') $errors[] = 'Cargo';
        if (empty($epiIds)) $errors[] = 'Ao menos um EPI';
        if (!$confirmed) $errors[] = 'Confirmação da entrega';

        // Evidências obrigatórias
        $selfie = $this->saveDataUrlImage($this->input('selfie_data', ''), 'selfie');
        $episPhoto = $this->saveDataUrlImage($this->input('epis_photo_data', ''), 'epis');
        $signature = $this->saveDataUrlImage($this->input('signature_data', ''), 'signature');

        if (!$selfie) $errors[] = 'Selfie do ' . $workerLabel;
        if (!$episPhoto) $errors[] = 'Foto do ' . $workerLabel . ' com os EPIs';
        if (!$signature) $errors[] = 'Assinatura';

        if (!empty($errors)) {
            $this->setFlash('error', 'Preencha os campos obrigatórios: ' . implode(', ', $errors) . '.');
            $this->redirect($redirect);
            return;
        }

        $deliveryId = EpiDelivery::create([
            'recipient_type' => $recipientType,
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

        $this->setFlash('success', ucfirst($isThird ? 'distribuição' : 'entrega') . " registrada para {$workerName}.");
        $this->redirect($redirect);
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
            $delivered = (float) $it['quantity'];
            $returned = (float) $it['returned_quantity'];
            $availableToReturn = max(0, $delivered - $returned);
            $out[] = [
                'id' => (int) $it['id'],
                'epi_id' => (int) $it['epi_id'],
                'epi_name' => $it['epi_name'],
                'ca' => $it['ca'],
                'quantity' => $delivered,
                'returned_quantity' => $returned,
                'available_to_return' => $availableToReturn,
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

    /**
     * Registra a devolução de um EPI. Só permite devolver o que o colaborador
     * de fato ainda possui (quantidade entregue menos o já devolvido).
     */
    public function returnStore(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido'], 405); return; }

        $itemId = (int) $this->input('delivery_item_id', 0);
        $item = EpiDeliveryItem::find($itemId);
        if (!$item) { $this->json(['error' => 'Item de entrega não encontrado.'], 404); return; }

        $delivered = (float) $item['quantity'];
        $alreadyReturned = EpiDeliveryItem::returnedQuantity($item['id']);
        $available = max(0, $delivered - $alreadyReturned);

        if ($available <= 0) {
            $this->json(['error' => 'Este EPI já foi totalmente devolvido.'], 400);
            return;
        }

        $qty = (float) $this->input('quantity', $available);
        if ($qty <= 0) { $this->json(['error' => 'Informe uma quantidade válida.'], 400); return; }
        if ($qty > $available) {
            $this->json(['error' => 'Quantidade superior à que o colaborador ainda possui (disponível: ' . $available . ').'], 400);
            return;
        }

        $photo = $this->saveDataUrlImage($this->input('photo_data', ''), 'return');
        $signature = $this->saveDataUrlImage($this->input('signature_data', ''), 'return_sign');
        $notes = trim($this->input('notes', ''));

        $delivery = EpiDelivery::find((int) $item['delivery_id']);

        EpiReturn::create([
            'delivery_item_id' => $item['id'],
            'epi_id' => $item['epi_id'],
            'epi_name' => $item['epi_name'],
            'ca' => $item['ca'],
            'quantity' => $qty,
            'worker_name' => $delivery['worker_name'] ?? '',
            'worker_document' => $delivery['worker_document'] ?? '',
            'performed_by' => $user['name'] ?? '',
            'performed_by_id' => $user['id'] ?? null,
            'photo_path' => $photo,
            'signature_path' => $signature,
            'notes' => $notes ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true]);
    }

    // ===================================================================
    // /historico-de-epi  — histórico de entregas, substituições e devoluções
    // ===================================================================

    public function history(): void
    {
        $user = $this->requireUser();

        // recipient_type do histórico: 'worker' (padrão) ou 'third_party'
        $recipientType = $this->input('tipo', 'worker') === 'terceiros' ? 'third_party' : 'worker';

        $deliveries = \App\Core\Database::fetchAll(
            "SELECT d.*, COUNT(i.id) AS item_count
             FROM epi_deliveries d
             LEFT JOIN epi_delivery_items i ON i.delivery_id = d.id
             WHERE d.recipient_type = ?
             GROUP BY d.id
             ORDER BY d.created_at DESC
             LIMIT 200",
            [$recipientType]
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

        // Substituições e devoluções: filtradas pelo tipo de destinatário através do JOIN
        $replacements = \App\Core\Database::fetchAll(
            "SELECT r.* FROM epi_replacements r
             INNER JOIN epi_delivery_items i ON i.id = r.delivery_item_id
             INNER JOIN epi_deliveries d ON d.id = i.delivery_id
             WHERE d.recipient_type = ?
             ORDER BY r.created_at DESC LIMIT 200",
            [$recipientType]
        );

        $returns = \App\Core\Database::fetchAll(
            "SELECT rt.* FROM epi_returns rt
             INNER JOIN epi_delivery_items i ON i.id = rt.delivery_item_id
             INNER JOIN epi_deliveries d ON d.id = i.delivery_id
             WHERE d.recipient_type = ?
             ORDER BY rt.created_at DESC LIMIT 200",
            [$recipientType]
        );

        $this->view('site.epi.history', [
            'user' => $user,
            'recipientType' => $recipientType,
            'deliveries' => $deliveries,
            'deliveryItems' => $deliveryItems,
            'replacements' => $replacements,
            'returns' => $returns,
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
