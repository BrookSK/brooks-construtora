# Spec: Novos Módulos — Urgência/Prazo, Antecedência, Lista Semanal, Controle Saveiro

## Visão Geral

Quatro módulos novos a serem implementados no sistema Brooks Construtora:

1. **Urgência + Prazo nos Pedidos** — campos novos em todos os pedidos
2. **Configuração de Antecedência Mínima** — validação ao criar pedido
3. **Lista de Materiais Semanal (Gerentes)** — cron + formulário + tracking
4. **Controle de Uso do Saveiro** — módulo novo independente

---

## Módulo 1: Urgência + Prazo nos Pedidos

### Campos Novos
- **urgency** — Nível de urgência: `low` (Baixa), `medium` (Média), `high` (Alta), `critical` (Crítica)
- **deadline** — Data prazo (quando precisa do material). Campo date.

### Onde aparece
- [x] Criação do pedido (formulário)
- [x] Detalhes do pedido (show.php)
- [x] Listagem de pedidos (index.php) — colunas + filtro/ordenação
- [x] Tela de cotação (quote.php) — exibição
- [x] Tela de aprovação (approval.php) — exibição
- [x] Edição financeira (financial_edit.php) — exibição
- [x] PDF do pedido
- [x] Planilha de exportação
- [x] Notificações (email/webhook) — incluir info de urgência e prazo

### Ordenação na listagem
- Poder ordenar por urgência (crítica primeiro) e por prazo (mais próximo primeiro)
- Filtro por nível de urgência

### Migration
```sql
ALTER TABLE purchase_orders
    ADD COLUMN urgency ENUM('low','medium','high','critical') DEFAULT 'medium' AFTER description,
    ADD COLUMN deadline DATE DEFAULT NULL AFTER urgency;
```

---

## Módulo 2: Configuração de Antecedência Mínima

### Settings (Config. Pedidos)
- **orders_min_days_enabled** — `0` ou `1` (ativar/desativar)
- **orders_min_days_count** — Quantidade de dias mínima de antecedência (ex: 3)
- **orders_min_days_mode** — `warn` (só avisa) ou `block` (bloqueia criação)
- **orders_min_days_message** — Mensagem personalizada do aviso

### Comportamento na criação do pedido
- Se ativado e modo = `warn`: exibe alerta amarelo com a mensagem configurada, mas permite continuar
- Se ativado e modo = `block`: se o prazo (deadline) for menor que hoje + X dias, bloqueia o submit e mostra erro
- Se desativado: nenhuma validação

### Onde configurar
- Tela Admin > Config. Pedidos (`/admin/orders/settings`)
- Novo card/seção "Antecedência Mínima"

---

## Módulo 3: Lista de Materiais Semanal (Gerentes)

### Conceito
Todo gerente de obra precisa enviar semanalmente a lista de materiais que vai precisar na semana seguinte. O sistema envia notificação toda terça-feira e cobra quem não preencheu.

### Cadastro de Gerentes
- Usar os PinUsers ou criar lista específica em Config. Pedidos
- Campos: nome, telefone, email, obra vinculada (opcional)

### Formulário Público (via link/token)
- Link único por gerente por semana (token)
- Campos: lista de materiais (nome, quantidade, observação), áudio, observação geral
- Após enviar: marca como "preenchido" para aquela semana

### Notificações
- **Terça-feira (cron)**: envia link do formulário por email + webhook (WhatsApp)
- **Quinta-feira (cron)**: se não preencheu, enviar cobrança pro gerente + pros gestores configurados
- Bloco de notificação em Config. Pedidos (novo): email gestores, telefone, webhook URL, modo

### Histórico e Tracking
- Listagem por semana: quem preencheu, quem não preencheu
- Poder ver o que cada gerente preencheu em cada semana
- Status visual: ✅ Preenchido | ❌ Não preencheu | ⏳ Pendente

### Tabelas
- `weekly_material_requests` — registro por gerente/semana
- `weekly_material_request_items` — itens de cada lista
- `weekly_material_managers` — cadastro dos gerentes (ou reusar pin_users)

---

## Módulo 4: Controle de Uso do Saveiro

### Conceito
Registro de quem pega e devolve o carro da empresa (Saveiro). Só pode pegar se ninguém estiver usando.

### Campos do Registro de Retirada
- **registered_by** — Quem fez o registro (automático, usuário logado)
- **driver_name** — Nome do motorista
- **pickup_date** — Data de saída
- **pickup_time** — Horário de saída
- **pickup_location** — Local de saída
- **pickup_km** — Quilometragem na saída
- **destination** — Destino final
- **pickup_notes** — Observação na retirada

### Campos do Registro de Devolução
- **return_date** — Data de chegada
- **return_time** — Horário de chegada
- **return_km** — Quilometragem na chegada
- **return_notes** — Observação na devolução
- **returned_by** — Quem registrou a devolução

### Regras de Negócio
- Só pode criar um novo registro de retirada se o último registro tiver devolução preenchida
- Se o cara já está com o carro (retirada sem devolução), ele não pode marcar nova retirada
- O formulário de devolução fica disponível apenas para quem está com o carro

### Telas
- Listagem de registros (histórico completo)
- Status atual: "Disponível" ou "Em uso por [Fulano] desde [data]"
- Formulário de retirada
- Formulário de devolução
- Dashboard com km total, último uso, etc.

### Tabela
```sql
CREATE TABLE vehicle_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_name VARCHAR(150) NOT NULL,
    registered_by VARCHAR(150) NOT NULL,
    registered_by_user_id INT UNSIGNED DEFAULT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    pickup_km INT UNSIGNED NOT NULL,
    destination VARCHAR(255) NOT NULL,
    pickup_notes TEXT DEFAULT NULL,
    return_date DATE DEFAULT NULL,
    return_time TIME DEFAULT NULL,
    return_km INT UNSIGNED DEFAULT NULL,
    return_notes TEXT DEFAULT NULL,
    returned_by VARCHAR(150) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Menu/Navegação
- Nova entrada no sidebar: "Saveiro" ou "Veículo" (com ícone bi-truck-front)
- Permissão: criar permissão `vehicle` ou reusar uma existente

---

## Ordem de Implementação

1. Módulo 1 — Urgência + Prazo (mais impacto imediato)
2. Módulo 2 — Config. Antecedência (complementa módulo 1)
3. Módulo 4 — Controle Saveiro (isolado, mais simples)
4. Módulo 3 — Lista Semanal (mais complexo, cron + formulário + tracking)
