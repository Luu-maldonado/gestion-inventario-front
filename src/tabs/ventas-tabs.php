<?php
function activeTab($tabName, $currentAction) {
    return $tabName === $currentAction
        ? 'class="tab-link active-tab-module"'
        : 'class="tab-link"';
}

$action = $_GET['action'] ?? 'generar';
?>

<div class="tabs-container">
    <a href="index.php?mod=ventas&action=generar" <?= activeTab('generar', $action) ?>>Registrar venta</a> 
    <a href="index.php?mod=ventas&action=lista-vent-x-art" <?= activeTab('lista-vent-x-art', $action) ?>>Listar ventas por artículo</a>
    <a href="index.php?mod=ventas&action=precio-art" <?= activeTab('precio-art', $action) ?>>Precio de venta de artículo</a> 
</div>