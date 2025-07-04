<?php
require 'vendor/autoload.php';
include 'partials/header.php';

$modulo = $_GET['mod'] ?? 'articulos';
$action = $_GET['action'] ?? '';

$baseDir = __DIR__ . '/src/modules/';

switch ($modulo) {
    case 'articulos':
        $file = "$baseDir/maestro_articulos/maestro-articulos-$action.php";
        break;
    case 'ordenes':
        $file = "$baseDir/orden_compra/orden-compra-$action.php";
        break;
    case 'proveedores':
        $file = "$baseDir/proveedores/proveedor-$action.php";
        break;
    case 'ventas':
        $file = "$baseDir/ventas/ventas-$action.php";
        break;
    default:
        $file = "$baseDir/maestro_articulos/maestro-articulos-abm-art.php";
        break;
}

if (!file_exists($file)) {
    $file = match ($modulo) {
        'articulos' => "$baseDir/maestro_articulos/maestro-articulos-abm-art.php",
        'ordenes' => "$baseDir/orden_compra/orden-compra-gestion-oc.php",
        'proveedores' => "$baseDir/proveedores/proveedor-abm-prov.php",
        'ventas' => "$baseDir/ventas/ventas-generar.php",
        default => "$baseDir/maestro_articulos/maestro-articulos-abm-art.php",
    };
}

include $file;
include 'partials/footer.php';