<?php
function activeTab($tabName, $currentAction) {
    return $tabName === $currentAction
        ? 'class="tab-link active-tab-module"'
        : 'class="tab-link"';
}

$action = $_GET['action'] ?? 'abm-art';
?>

<div class="tabs-container">
  <a href="index.php?mod=articulos&action=abm-art" <?= activeTab('abm-art', $action) ?>>ABM Artículos</a> 
    <a href="index.php?mod=articulos&action=prov-pred" <?= activeTab('prov-pred', $action) ?>>Proveedor Predeterminado</a> 
    <a href="index.php?mod=articulos&action=lista-art-reponer" <?= activeTab('lista-art-reponer', $action) ?>>Lista de Articulos a Reponer</a> 
    <a href="index.php?mod=articulos&action=lista-art-faltantes" <?= activeTab('lista-art-faltantes', $action) ?>>Lista de Articulos Faltantes</a> 
    <a href="index.php?mod=articulos&action=lista-prov-x-art" <?= activeTab('lista-prov-x-art', $action) ?>>Lista de Proveedores por Artículo</a>
</div>