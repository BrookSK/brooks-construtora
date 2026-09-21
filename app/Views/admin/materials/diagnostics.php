<?php $pageTitle = 'Diagnóstico de Materiais'; ?>
<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">🔍 Diagnóstico de Materiais</h1>
            <p class="text-muted">Verificação das especificações e listas de materiais</p>
        </div>
        <div class="col-auto">
            <a href="/admin/materials" class="btn btn-outline-secondary">← Voltar</a>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0"><?= number_format($totalMaterials, 0, ',', '.') ?></h2>
                    <small>Materiais Ativos</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2 class="mb-0"><?= number_format($totalSpecs, 0, ',', '.') ?></h2>
                    <small>Especificações Únicas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
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
                            <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                            <p>Nenhuma lista criada!</p>
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

    <!-- Instruções -->
    <div class="alert alert-info">
        <h5>ℹ️ Como funciona:</h5>
        <ol class="mb-0">
            <li><strong>Reimportação:</strong> Atualiza os materiais pelo ID, incluindo a coluna <code>specification</code></li>
            <li><strong>Recriar Listas:</strong> Agrupa os materiais pela coluna <code>specification</code> e cria uma lista para cada valor único</li>
            <li>Se as especificações acima estão corretas (ex: "Material Hidráulico", "Material Elétrico"), vá em <a href="/admin/material-lists">Listas de Materiais</a> e clique em "Recriar Listas"</li>
        </ol>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
