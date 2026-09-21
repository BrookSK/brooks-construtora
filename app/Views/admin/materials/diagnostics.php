<?php $pageTitle = 'Diagnóstico de Materiais'; $currentPage = 'materials'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">🔍 Diagnóstico de Materiais</h1>
        <p class="text-muted mb-0">Verificação das especificações e listas de materiais</p>
    </div>
    <a href="/admin/materials" class="btn btn-outline-secondary">← Voltar</a>
</div>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= number_format((int) $totalMaterials, 0, ',', '.') ?></h2>
                <small>Materiais Ativos</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= number_format((int) $totalSpecs, 0, ',', '.') ?></h2>
                <small>Especificações Únicas</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h2 class="mb-0"><?= count($templates) ?></h2>
                <small>Listas Criadas</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Especificações -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <strong>📋 Especificações Únicas (specification)</strong>
                <br><small>Estes valores são usados para criar as Listas de Materiais</small>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-striped mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Especificação</th>
                            <th class="text-end">Qtd</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($specs as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['spec_name']) ?></td>
                            <td class="text-end"><?= $s['total'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Listas Existentes -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <strong>📁 Listas de Materiais (templates)</strong>
                <br><small>Listas criadas no sistema</small>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($templates)): ?>
                    <div class="p-3 text-center text-muted">
                        <p class="mb-2">⚠️ Nenhuma lista criada!</p>
                        <a href="/admin/material-lists" class="btn btn-primary btn-sm">Ir para Listas</a>
                    </div>
                <?php else: ?>
                <table class="table table-sm table-striped mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Nome da Lista</th>
                            <th class="text-end">Itens</th>
                            <th>Criada em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['name']) ?></td>
                            <td class="text-end"><?= $t['item_count'] ?></td>
                            <td><small><?= date('d/m/Y', strtotime($t['created_at'])) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Amostra de Materiais -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <strong>📦 Amostra de Materiais (primeiros 30)</strong>
        <br><small>Verifique se o campo "specification" está preenchido corretamente</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="sticky-top bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th style="background: #d4edda;">Specification (usado p/ listas)</th>
                        <th>Classification</th>
                        <th>Categoria</th>
                        <th>Unidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['name']) ?></td>
                        <td style="background: #d4edda;">
                            <?php if (empty($m['specification'])): ?>
                                <span class="text-danger">⚠️ VAZIO</span>
                            <?php else: ?>
                                <?= htmlspecialchars($m['specification']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['classification'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['category_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['unit_abbr'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Categorias -->
<div class="card mb-4">
    <div class="card-header">
        <strong>🏷️ Categorias de Materiais</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($categories as $c): ?>
            <div class="col-md-3 col-sm-4 col-6 mb-2">
                <span class="badge bg-secondary"><?= htmlspecialchars($c['name']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Listas Órfãs -->
<div class="card mb-4 border-danger">
    <div class="card-header bg-danger text-white">
        <strong>⚠️ Listas que NÃO batem com nenhuma especificação atual (<?= count($orphanTemplates ?? []) ?>)</strong>
        <br><small>Se houver muitas aqui, essas listas foram criadas por outra coluna (ex: classification) ou antes da reimportação</small>
    </div>
    <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
        <?php if (empty($orphanTemplates)): ?>
            <div class="p-3 text-success">✓ Nenhuma lista órfã. Todas as listas correspondem a especificações.</div>
        <?php else: ?>
        <table class="table table-sm table-striped mb-0">
            <thead class="sticky-top bg-light"><tr><th>Nome da Lista (órfã)</th><th class="text-end">Itens</th></tr></thead>
            <tbody>
                <?php foreach ($orphanTemplates as $o): ?>
                <tr><td><?= htmlspecialchars($o['name']) ?></td><td class="text-end"><?= $o['item_count'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Ação de rebuild direto -->
<div class="alert alert-warning">
    <h5>🔧 Recriar listas AGORA (teste direto)</h5>
    <p class="mb-2">Este botão executa a recriação diretamente no servidor e mostra o resultado em texto puro (sem modal, sem confirmação). Use para diagnosticar se a recriação funciona.</p>
    <a href="/admin/materials/rebuild-lists" class="btn btn-warning" target="_blank">
        ▶ Executar Recriação de Listas (debug)
    </a>
</div>

<!-- Instruções -->
<div class="alert alert-info">
    <h5>ℹ️ Como funciona:</h5>
    <ol class="mb-0">
        <li><strong>Reimportação:</strong> Atualiza os materiais pelo ID, incluindo a coluna <code>specification</code></li>
        <li><strong>Recriar Listas:</strong> Agrupa os materiais pela coluna <code>specification</code> e cria uma lista para cada valor único</li>
    </ol>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
