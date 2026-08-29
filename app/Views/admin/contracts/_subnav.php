<?php
// Sub-navegação do módulo Elaboração de Contrato.
// Define $contractTab antes de incluir (ex.: 'contracts' | 'settings' | 'diagnostics').
$contractTab = $contractTab ?? 'contracts';
?>
<ul class="nav nav-pills mb-3 gap-1">
    <li class="nav-item">
        <a class="nav-link <?= $contractTab === 'contracts' ? 'active' : '' ?>" href="/admin/contracts">
            <i class="bi bi-files"></i> Contratos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $contractTab === 'settings' ? 'active' : '' ?>" href="/admin/contracts/settings">
            <i class="bi bi-gear"></i> Configurações
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $contractTab === 'diagnostics' ? 'active' : '' ?>" href="/admin/contracts/diagnostics">
            <i class="bi bi-activity"></i> Diagnóstico
        </a>
    </li>
</ul>
