<?php
function activeTab($tabName, $currentAction) {
    return $tabName === $currentAction
        ? 'class="tab-link active-tab-module"'
        : 'class="tab-link"';
}

$action = $_GET['action'] ?? 'calc';
?>

<div class="tabs-container">
    <a href="index.php?mod=demanda&action=calc" <?= activeTab('calc', $action) ?>>Proyección de demanda</a> 
</div>