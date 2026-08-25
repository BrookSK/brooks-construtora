<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Cliente da API do Nibo (Gestão Financeira / Empresas).
 * Base: https://api.nibo.com.br/empresas/v1
 * Autenticação: header "apitoken".
 * Consultas GET usam OData ($filter, $orderby, $top, $skip).
 *
 * O token é lido de Settings (chave nibo_api_token) ou pode ser passado
 * explicitamente (útil para testar na página /admin/dev/nibo).
 */
class NiboService
{
    private const BASE_URL = 'https://api.nibo.com.br/empresas/v1';

    public static function token(): string
    {
        return (string) Setting::get('nibo_api_token', '');
    }

    public static function baseUrl(): string
    {
        return self::BASE_URL;
    }

    /**
     * Executa uma requisição à API do Nibo.
     *
     * @param string      $method  GET|POST|PUT|DELETE
     * @param string      $path    Ex.: '/customers' ou '/customers/{id}'
     * @param array       $query   Parâmetros de query (OData incluso)
     * @param array|null  $body    Corpo JSON (POST/PUT)
     * @param string|null $token   Sobrescreve o token de Settings
     *
     * @return array{ok:bool, status:int, url:string, request:array, response:mixed, error?:string, duration_ms:int}
     */
    public static function request(string $method, string $path, array $query = [], ?array $body = null, ?string $token = null): array
    {
        $token = $token ?: self::token();
        $method = strtoupper($method);
        $url = self::BASE_URL . '/' . ltrim($path, '/');

        if (!empty($query)) {
            // http_build_query codifica os $ do OData; montamos manualmente
            $pairs = [];
            foreach ($query as $k => $v) {
                if ($v === null || $v === '') continue;
                $pairs[] = rawurlencode($k) . '=' . rawurlencode((string) $v);
            }
            if ($pairs) $url .= '?' . implode('&', $pairs);
        }

        $headers = [
            'Accept: application/json',
            'ApiToken: ' . $token,
        ];

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $opts[CURLOPT_POSTFIELDS] = $json;
            $headers[] = 'Content-Type: application/json';
        }

        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $start = microtime(true);
        $raw = curl_exec($ch);
        $duration = (int) round((microtime(true) - $start) * 1000);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $result = [
            'ok' => false,
            'status' => $status,
            'url' => $url,
            'request' => [
                'method' => $method,
                'headers' => ['ApiToken' => self::maskToken($token), 'Accept' => 'application/json'],
                'body' => $body,
            ],
            'response' => null,
            'duration_ms' => $duration,
        ];

        if ($raw === false) {
            $result['error'] = 'Falha de conexão: ' . ($curlErr ?: 'desconhecida');
            return $result;
        }

        $decoded = json_decode($raw, true);
        $result['response'] = ($decoded !== null) ? $decoded : $raw;
        $result['ok'] = ($status >= 200 && $status < 300);
        if (!$result['ok'] && empty($result['error'])) {
            $result['error'] = "HTTP {$status}";
        }
        return $result;
    }

    private static function maskToken(string $token): string
    {
        if ($token === '') return '(vazio)';
        $len = strlen($token);
        if ($len <= 8) return str_repeat('*', $len);
        return substr($token, 0, 4) . str_repeat('*', $len - 8) . substr($token, -4);
    }

    /**
     * Catálogo de endpoints da API do Nibo usados no testador.
     * Cada item: key, group, label, method, path, description, sample_query, sample_body,
     * needs_id (se o path tem {id}), doc (url da doc).
     */
    public static function catalog(): array
    {
        return [
            // ── Empresas ───────────────────────────────────────────────
            [
                'key' => 'organizations_list', 'group' => 'Empresas', 'label' => 'Listar empresas',
                'method' => 'GET', 'path' => '/organizations',
                'description' => 'Lista as empresas que o usuário do token tem acesso.',
                'sample_query' => ['$top' => '50'],
                'doc' => 'https://nibo.readme.io/reference/empresas',
            ],

            // ── Contas & Extratos ──────────────────────────────────────
            [
                'key' => 'accounts_list', 'group' => 'Contas', 'label' => 'Listar contas bancárias',
                'method' => 'GET', 'path' => '/accounts',
                'description' => 'Lista todas as contas bancárias da empresa (OData).',
                'sample_query' => ['$orderby' => 'name', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/listar-contas',
            ],
            [
                'key' => 'accounts_update', 'group' => 'Contas', 'label' => 'Atualizar conta bancária',
                'method' => 'PUT', 'path' => '/accounts/{id}', 'needs_id' => true,
                'description' => 'Edita uma conta bancária (informe o accountId).',
                'sample_body' => [
                    'Name' => 'Conta Corrente Atualizada',
                    'DateOfOpenBalance' => date('Y-m-d'),
                    'BankAgency' => '1234',
                    'BankAccount' => '56789',
                    'BankAccountVerificationNumber' => 1,
                    'OpenBalance' => 100.00,
                ],
                'doc' => 'https://nibo.readme.io/reference/atualizar-conta',
            ],

            // ── Clientes ───────────────────────────────────────────────
            [
                'key' => 'customers_list', 'group' => 'Clientes', 'label' => 'Listar clientes',
                'method' => 'GET', 'path' => '/customers',
                'description' => 'Lista os clientes da empresa (OData).',
                'sample_query' => ['$orderby' => 'name', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/criar-um-cliente-json',
            ],
            [
                'key' => 'customers_get', 'group' => 'Clientes', 'label' => 'Obter cliente por ID',
                'method' => 'GET', 'path' => '/customers/{id}', 'needs_id' => true,
                'description' => 'Retorna os detalhes de um cliente específico.',
            ],
            [
                'key' => 'customers_create', 'group' => 'Clientes', 'label' => 'Criar cliente',
                'method' => 'POST', 'path' => '/customers',
                'description' => 'Cria um novo cliente.',
                'sample_body' => [
                    'name' => 'Cliente Teste API',
                    'document' => ['number' => '12345678000199', 'type' => 'CNPJ'],
                    'communication' => ['email' => 'teste@exemplo.com', 'phone' => '11999999999'],
                ],
                'doc' => 'https://nibo.readme.io/reference/criar-um-cliente-json',
            ],
            [
                'key' => 'customers_update', 'group' => 'Clientes', 'label' => 'Atualizar cliente por ID',
                'method' => 'PUT', 'path' => '/customers/{id}', 'needs_id' => true,
                'description' => 'Atualiza um cliente. Envie todos os campos do cadastro.',
                'sample_body' => ['name' => 'Cliente Atualizado'],
                'doc' => 'https://nibo.readme.io/reference/atualizar-cliente',
            ],
            [
                'key' => 'customers_delete', 'group' => 'Clientes', 'label' => 'Excluir cliente',
                'method' => 'DELETE', 'path' => '/customers/{id}', 'needs_id' => true,
                'description' => 'Exclui um cliente da empresa.',
                'doc' => 'https://nibo.readme.io/reference/arquivar-cliente-copy',
            ],
            [
                'key' => 'customers_schedules', 'group' => 'Clientes', 'label' => 'Agendamentos por cliente',
                'method' => 'GET', 'path' => '/customers/{id}/schedules', 'needs_id' => true,
                'description' => 'Lista agendamentos associados a um cliente (OData).',
                'sample_query' => ['$orderby' => 'dueDate', '$top' => '20'],
                'doc' => 'https://nibo.readme.io/reference/buscar-agendamentos-por-cliente',
            ],

            // ── Fornecedores ───────────────────────────────────────────
            [
                'key' => 'suppliers_list', 'group' => 'Fornecedores', 'label' => 'Listar fornecedores',
                'method' => 'GET', 'path' => '/suppliers',
                'description' => 'Lista os fornecedores da empresa (OData).',
                'sample_query' => ['$orderby' => 'name', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/criar-um-fornecedor',
            ],
            [
                'key' => 'suppliers_get', 'group' => 'Fornecedores', 'label' => 'Obter fornecedor por ID',
                'method' => 'GET', 'path' => '/suppliers/{id}', 'needs_id' => true,
                'description' => 'Retorna os detalhes de um fornecedor específico.',
            ],
            [
                'key' => 'suppliers_create', 'group' => 'Fornecedores', 'label' => 'Criar fornecedor',
                'method' => 'POST', 'path' => '/suppliers',
                'description' => 'Cria um novo fornecedor.',
                'sample_body' => [
                    'name' => 'Fornecedor Teste API',
                    'document' => ['number' => '12345678000199', 'type' => 'CNPJ'],
                ],
                'doc' => 'https://nibo.readme.io/reference/criar-um-fornecedor',
            ],
            [
                'key' => 'suppliers_update', 'group' => 'Fornecedores', 'label' => 'Atualizar fornecedor por ID',
                'method' => 'PUT', 'path' => '/suppliers/{id}', 'needs_id' => true,
                'description' => 'Atualiza um fornecedor. Envie todos os campos do cadastro.',
                'sample_body' => ['name' => 'Fornecedor Atualizado'],
                'doc' => 'https://nibo.readme.io/reference/atualiza-fornecedor-por-id',
            ],
            [
                'key' => 'suppliers_delete', 'group' => 'Fornecedores', 'label' => 'Excluir fornecedor',
                'method' => 'DELETE', 'path' => '/suppliers/{id}', 'needs_id' => true,
                'description' => 'Exclui um fornecedor da empresa.',
            ],

            // ── Categorias ─────────────────────────────────────────────
            [
                'key' => 'categories_list', 'group' => 'Categorias', 'label' => 'Listar categorias',
                'method' => 'GET', 'path' => '/categories',
                'description' => 'Lista as categorias de lançamento (OData).',
                'sample_query' => ['$orderby' => 'name', '$top' => '50'],
            ],
            [
                'key' => 'categories_create', 'group' => 'Categorias', 'label' => 'Criar categoria',
                'method' => 'POST', 'path' => '/categories',
                'description' => 'Cria uma nova categoria.',
                'sample_body' => ['name' => 'Categoria Teste API', 'type' => 'Out'],
            ],

            // ── Centros de custo ───────────────────────────────────────
            [
                'key' => 'costcenters_list', 'group' => 'Centros de Custo', 'label' => 'Listar centros de custo',
                'method' => 'GET', 'path' => '/costcenters',
                'description' => 'Lista os centros de custo (OData).',
                'sample_query' => ['$orderby' => 'name', '$top' => '50'],
            ],
            [
                'key' => 'costcenters_create', 'group' => 'Centros de Custo', 'label' => 'Criar centro de custo',
                'method' => 'POST', 'path' => '/costcenters',
                'description' => 'Cria um novo centro de custo.',
                'sample_body' => ['name' => 'Centro de Custo Teste API'],
                'doc' => 'https://nibo.readme.io/reference/criar-centro-de-custo',
            ],

            // ── Pagamentos (débito / contas a pagar) ───────────────────
            [
                'key' => 'debit_list', 'group' => 'Pagamentos', 'label' => 'Listar pagamentos em aberto',
                'method' => 'GET', 'path' => '/schedules/debit',
                'description' => 'Lista pagamentos agendados em aberto (OData).',
                'sample_query' => ['$orderby' => 'dueDate', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/agendar-pagamento',
            ],
            [
                'key' => 'debit_create', 'group' => 'Pagamentos', 'label' => 'Agendar pagamento',
                'method' => 'POST', 'path' => '/schedules/debit',
                'description' => 'Cria um agendamento de pagamento.',
                'sample_body' => [
                    'stakeholderId' => 'ID_DO_FORNECEDOR',
                    'categoryId' => 'ID_DA_CATEGORIA',
                    'value' => 100.00,
                    'dueDate' => date('Y-m-d', strtotime('+7 days')),
                    'description' => 'Pagamento teste via API',
                ],
                'doc' => 'https://nibo.readme.io/reference/agendar-pagamento',
            ],
            [
                'key' => 'debit_delete', 'group' => 'Pagamentos', 'label' => 'Excluir pagamento agendado',
                'method' => 'DELETE', 'path' => '/schedules/debit/{id}', 'needs_id' => true,
                'description' => 'Exclui um agendamento de pagamento.',
                'doc' => 'https://nibo.readme.io/reference/excluir-pagamento-agendado',
            ],
            [
                'key' => 'payments_list', 'group' => 'Pagamentos', 'label' => 'Listar pagamentos realizados (contas pagas)',
                'method' => 'GET', 'path' => '/payments',
                'description' => 'Lista pagamentos já realizados (OData).',
                'sample_query' => ['$orderby' => 'paymentDate', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/pagar-1',
            ],

            // ── Recebimentos (crédito / contas a receber) ──────────────
            [
                'key' => 'credit_list', 'group' => 'Recebimentos', 'label' => 'Listar recebimentos em aberto',
                'method' => 'GET', 'path' => '/schedules/credit',
                'description' => 'Lista recebimentos agendados em aberto (OData).',
                'sample_query' => ['$orderby' => 'dueDate', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/consulta-recebimento-em-aberto',
            ],
            [
                'key' => 'credit_create', 'group' => 'Recebimentos', 'label' => 'Agendar recebimento',
                'method' => 'POST', 'path' => '/schedules/credit',
                'description' => 'Cria um agendamento de recebimento.',
                'sample_body' => [
                    'stakeholderId' => 'ID_DO_CLIENTE',
                    'categoryId' => 'ID_DA_CATEGORIA',
                    'value' => 100.00,
                    'dueDate' => date('Y-m-d', strtotime('+7 days')),
                    'description' => 'Recebimento teste via API',
                ],
                'doc' => 'https://nibo.readme.io/reference/agendar-recebimento-json',
            ],
            [
                'key' => 'credit_delete', 'group' => 'Recebimentos', 'label' => 'Excluir recebimento agendado',
                'method' => 'DELETE', 'path' => '/schedules/credit/{id}', 'needs_id' => true,
                'description' => 'Exclui um agendamento de recebimento.',
                'doc' => 'https://nibo.readme.io/reference/excluir-agendamento-de-recebimento',
            ],
            [
                'key' => 'receipts_list', 'group' => 'Recebimentos', 'label' => 'Listar recebimentos realizados (contas recebidas)',
                'method' => 'GET', 'path' => '/receipts',
                'description' => 'Lista recebimentos já realizados (OData).',
                'sample_query' => ['$orderby' => 'receiptDate', '$top' => '20', '$skip' => '0'],
                'doc' => 'https://nibo.readme.io/reference/receber-1',
            ],

            // ── Sócios (Contatos) ──────────────────────────────────────
            [
                'key' => 'partners_list', 'group' => 'Sócios', 'label' => 'Listar sócios',
                'method' => 'GET', 'path' => '/partners',
                'description' => 'Lista os sócios/contatos da empresa (OData).',
                'sample_query' => ['$orderby' => 'name', '$top' => '50'],
                'doc' => 'https://nibo.readme.io/reference/criar-socio',
            ],
            [
                'key' => 'partners_create', 'group' => 'Sócios', 'label' => 'Criar sócio',
                'method' => 'POST', 'path' => '/partners',
                'description' => 'Cria um novo sócio/contato.',
                'sample_body' => [
                    'name' => 'Sócio Teste API',
                    'document' => ['number' => '12345678901', 'type' => 'CPF'],
                    'communication' => ['email' => 'teste@exemplo.com', 'phone' => '1122334455'],
                ],
                'doc' => 'https://nibo.readme.io/reference/criar-socio',
            ],

            // ── Agendamentos: Anotações ────────────────────────────────
            [
                'key' => 'schedule_annotation_create', 'group' => 'Agendamentos · Anotações', 'label' => 'Criar anotação no agendamento',
                'method' => 'POST', 'path' => '/public/schedules/{scheduleId}/annotations',
                'params' => ['scheduleId' => 'ID do agendamento'],
                'description' => 'Adiciona uma anotação a um agendamento.',
                'sample_body' => ['annotation' => 'Observação de teste via API'],
                'doc' => 'https://nibo.readme.io/reference/criar-anotacao',
            ],
            [
                'key' => 'schedule_annotation_delete', 'group' => 'Agendamentos · Anotações', 'label' => 'Excluir anotação do agendamento',
                'method' => 'DELETE', 'path' => '/public/schedules/{scheduleId}/annotations/{annotationId}',
                'params' => ['scheduleId' => 'ID do agendamento', 'annotationId' => 'ID da anotação'],
                'description' => 'Remove uma anotação de um agendamento.',
                'doc' => 'https://nibo.readme.io/reference/excluir-anotacao',
            ],

            // ── Agendamentos: Arquivos ─────────────────────────────────
            [
                'key' => 'schedule_debit_file_delete', 'group' => 'Agendamentos · Arquivos', 'label' => 'Excluir arquivo no agendamento de pagamento',
                'method' => 'DELETE', 'path' => '/schedules/debit/{scheduleId}/files/{fileId}',
                'params' => ['scheduleId' => 'ID do agendamento', 'fileId' => 'ID do arquivo'],
                'description' => 'Exclui um arquivo anexado a um agendamento de pagamento.',
                'doc' => 'https://nibo.readme.io/reference/excluir-arquivo-agendamento-pagamento',
            ],
            [
                'key' => 'schedule_credit_file_delete', 'group' => 'Agendamentos · Arquivos', 'label' => 'Excluir arquivo no agendamento de recebimento',
                'method' => 'DELETE', 'path' => '/schedules/credit/{scheduleId}/files/{fileId}',
                'params' => ['scheduleId' => 'ID do agendamento', 'fileId' => 'ID do arquivo'],
                'description' => 'Exclui um arquivo anexado a um agendamento de recebimento.',
            ],
        ];
    }

    /**
     * Localiza um endpoint do catálogo pela chave.
     */
    public static function findEndpoint(string $key): ?array
    {
        foreach (self::catalog() as $ep) {
            if ($ep['key'] === $key) return $ep;
        }
        return null;
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEITURA — helpers para o Dashboard Financeiro (somente GET)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * GET paginado com OData. Percorre $skip/$top (limite 500 por página)
     * até esgotar os registros. Retorna a lista completa de itens.
     */
    public static function getAllPaged(string $path, string $orderBy = 'name', array $extraQuery = [], ?string $token = null, int $pageSize = 500, int $maxPages = 50): array
    {
        $all = [];
        $skip = 0;
        for ($page = 0; $page < $maxPages; $page++) {
            $query = array_merge($extraQuery, [
                '$orderby' => $orderBy,
                '$top' => (string) $pageSize,
                '$skip' => (string) $skip,
            ]);
            $res = self::request('GET', $path, $query, null, $token);
            if (!$res['ok']) {
                // Propaga erro para o chamador tratar (mantém dados antigos na tela)
                throw new \RuntimeException("Falha ao ler {$path}: HTTP {$res['status']} " . ($res['error'] ?? ''));
            }
            $items = self::extractItems($res['response']);
            $all = array_merge($all, $items);
            if (count($items) < $pageSize) break; // última página
            $skip += $pageSize;
        }
        return $all;
    }

    /**
     * A API do Nibo retorna listas em formatos variados: às vezes um array
     * direto, às vezes { items: [...] } ou { value: [...] } com "count".
     */
    private static function extractItems($response): array
    {
        if (is_array($response)) {
            if (isset($response['items']) && is_array($response['items'])) return $response['items'];
            if (isset($response['value']) && is_array($response['value'])) return $response['value'];
            // array numérico simples
            if (array_keys($response) === range(0, count($response) - 1)) return $response;
        }
        return [];
    }

    /**
     * Executa o fluxo COMPLETO de sincronização (somente leitura):
     *  1) listas mestres  2) saldos  3) agendamentos  4) consolidação.
     * Retorna a estrutura pronta para o dashboard + eventuais erros parciais.
     *
     * @return array{ok:bool, generated_at:string, masters:array, accounts:array,
     *                payables:array, receivables:array, totals:array, errors:array}
     */
    public static function syncAll(?string $token = null, ?string $from = null, ?string $to = null): array
    {
        $token = $token ?: self::token();
        $errors = [];

        // Janela padrão: 12 meses atrás até 12 meses à frente
        $from = $from ?: date('Y-m-d', strtotime('-12 months'));
        $to = $to ?: date('Y-m-d', strtotime('+12 months'));

        // ── Etapa 1: listas mestres ─────────────────────────────────────
        $suppliers = $customers = $costcenters = $categories = $accounts = [];
        try { $suppliers = self::getAllPaged('/suppliers', 'name', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
        try { $customers = self::getAllPaged('/customers', 'name', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
        try { $costcenters = self::getAllPaged('/costcenters', 'name', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
        try { $categories = self::getAllPaged('/categories', 'name', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }

        // ── Etapa 2: saldos das contas ──────────────────────────────────
        try { $accounts = self::getAllPaged('/accounts', 'name', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }

        // Índices id→nome para enriquecimento
        $idxSup = self::indexById($suppliers);
        $idxCus = self::indexById($customers);
        $idxCc  = self::indexById($costcenters);
        $idxCat = self::indexById($categories);

        // ── Etapa 3: agendamentos (débito/crédito) ──────────────────────
        $payablesRaw = $receivablesRaw = [];
        try { $payablesRaw = self::getAllPaged('/schedules/debit', 'dueDate', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }
        try { $receivablesRaw = self::getAllPaged('/schedules/credit', 'dueDate', [], $token); } catch (\Throwable $e) { $errors[] = $e->getMessage(); }

        // ── Etapa 4: consolidação (enriquecer + status) ─────────────────
        $payables = array_map(fn($s) => self::enrichSchedule($s, $idxSup, $idxCc, $idxCat, 'payable'), $payablesRaw);
        $receivables = array_map(fn($s) => self::enrichSchedule($s, $idxCus, $idxCc, $idxCat, 'receivable'), $receivablesRaw);

        // Saldo total
        $balance = 0.0;
        foreach ($accounts as $a) {
            $balance += (float) ($a['balance'] ?? $a['currentBalance'] ?? $a['value'] ?? 0);
        }

        return [
            'ok' => empty($errors),
            'generated_at' => date('Y-m-d H:i:s'),
            'masters' => [
                'suppliers' => $suppliers,
                'customers' => $customers,
                'costcenters' => $costcenters,
                'categories' => $categories,
            ],
            'accounts' => $accounts,
            'payables' => $payables,
            'receivables' => $receivables,
            'totals' => [
                'balance' => $balance,
                'suppliers' => count($suppliers),
                'customers' => count($customers),
                'payables' => count($payables),
                'receivables' => count($receivables),
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Cria um índice id => nome a partir de uma lista de mestres.
     */
    private static function indexById(array $list): array
    {
        $idx = [];
        foreach ($list as $item) {
            $id = $item['id'] ?? $item['Id'] ?? $item['costCenterId'] ?? $item['categoryId'] ?? null;
            $name = $item['name'] ?? $item['Name'] ?? $item['description'] ?? '';
            if ($id !== null) $idx[(string) $id] = $name;
        }
        return $idx;
    }

    /**
     * Enriquate um agendamento com nomes legíveis e status derivado.
     */
    private static function enrichSchedule(array $s, array $idxContact, array $idxCc, array $idxCat, string $type): array
    {
        // Descobrir IDs (a API varia os nomes de campo)
        $contactId = $s['stakeholderId'] ?? $s['stakeholder']['id'] ?? $s['customerId'] ?? $s['supplierId'] ?? null;
        $contactName = $s['stakeholder']['name'] ?? null;
        if (!$contactName && $contactId !== null && isset($idxContact[(string) $contactId])) {
            $contactName = $idxContact[(string) $contactId];
        }

        $ccId = $s['costCenterId'] ?? $s['costCenter']['id'] ?? null;
        $ccName = $s['costCenter']['name'] ?? ($ccId !== null ? ($idxCc[(string) $ccId] ?? null) : null);
        // Alguns retornos trazem rateio de centros de custo em array
        if (!$ccName && !empty($s['costCenters']) && is_array($s['costCenters'])) {
            $first = $s['costCenters'][0] ?? null;
            if ($first) {
                $fid = $first['costCenterId'] ?? $first['id'] ?? null;
                $ccName = $first['name'] ?? ($fid !== null ? ($idxCc[(string) $fid] ?? null) : null);
            }
        }

        $catId = $s['categoryId'] ?? $s['category']['id'] ?? null;
        $catName = $s['category']['name'] ?? ($catId !== null ? ($idxCat[(string) $catId] ?? null) : null);

        $value = (float) ($s['value'] ?? $s['amount'] ?? 0);
        $dueDate = $s['dueDate'] ?? $s['accrualDate'] ?? null;
        $isPaid = !empty($s['isPaid']) || !empty($s['paid']) || !empty($s['paymentDate']) || !empty($s['isFullyPaid']);

        // Status derivado (só leitura): pago / vencido / em aberto
        $status = 'open';
        if ($isPaid) {
            $status = 'paid';
        } elseif ($dueDate) {
            $today = strtotime(date('Y-m-d'));
            $due = strtotime(date('Y-m-d', strtotime($dueDate)));
            if ($due !== false && $due < $today) $status = 'overdue';
        }

        return [
            'id' => $s['scheduleId'] ?? $s['id'] ?? null,
            'type' => $type,
            'due_date' => $dueDate,
            'value' => $value,
            'description' => $s['description'] ?? '',
            'contact_id' => $contactId,
            'contact_name' => $contactName ?: '—',
            'cost_center' => $ccName ?: '—',
            'category' => $catName ?: '—',
            'status' => $status,
            'is_paid' => $isPaid,
        ];
    }
}
