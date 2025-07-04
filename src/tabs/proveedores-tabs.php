<?php
function activeTab($tabName, $currentAction) {
    return $tabName === $currentAction
        ? 'class="tab-link active-tab-module"'
        : 'class="tab-link"';
}

$action = $_GET['action'] ?? 'abm-prov';
?>

<div class="tabs-container">
    <a href="index.php?mod=proveedores&action=abm-prov" <?= activeTab('abm-prov', $action) ?> style="display: inline-block; margin-right: 1em;">ABM Proveedores</a>
    <a href="index.php?mod=proveedores&action=prov-art" <?= activeTab('prov-art', $action) ?> style="display: inline-block; margin-right: 1em;">Gestión proveedor-artículo</a>
    <a href="index.php?mod=proveedores&action=listado-art-x-prov" <?= activeTab('listado-art-x-prov', $action) ?> style="display: inline-block; margin-right: 1em;">Lista de artículos por proveedor</a>
    <a href="index.php?mod=proveedores&action=restaurar-susp" <?= activeTab('restaurar-susp', $action) ?> style="display: inline-block; margin-right: 1em;">Restaurar proveedores suspendidos</a>
    <a href="index.php?mod=proveedores&action=historico-est-prov" <?= activeTab('historico-est-prov', $action) ?> style="display: inline-block; margin-right: 1em;">Consultar histórico de estado de proveedores</a>
</div>
