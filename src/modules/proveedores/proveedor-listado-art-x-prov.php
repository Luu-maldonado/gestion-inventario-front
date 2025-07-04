<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'listado-art-x-prov';

include __DIR__ . '/../../tabs/proveedores-tabs.php';
?>

<?php if ($action === 'listado-art-x-prov'): ?>
<div class="proveedor-articulos-container">

  <div  class="columna">
    <h4>Proveedores activos</h4>
    <ul id="listaProveedoresActivos" class="lista-articulos"></ul>
  </div>

  <div class="columna columna-articulos-relacionados">
    <h4>Artículos relacionados</h4>
    <div class="table-wrapper">
    <table id="tablaArticulosProveedor" class="tabla-base">
      <thead>
        <tr>
          <th>ID Artículo</th>
          <th>Nombre Artículo</th>
          <th>Precio Unitario</th>
          <th>Tiempo Entrega (días)</th>
          <th>Costo Pedido</th>
          <th>Predeterminado</th>
        </tr>
      </thead>
      <tbody>

      </tbody>
    </table>
    </div>
  </div>
</div>

<script>
let proveedorSeleccionado = null;

async function cargarProveedoresActivos() {
  const lista = document.getElementById('listaProveedoresActivos');
  lista.innerHTML = '';
  const res = await fetch('http://localhost:5000/Proveedor/activos');
  const data = await res.json();
  data.forEach(p => {
    const li = document.createElement('li');
    li.textContent = `#${p.idProveedor} - ${p.nombreProveedor}`;
    li.className = 'articulo-item'; 
    li.style.cursor = 'pointer';
    li.onclick = () => seleccionarProveedor(p.idProveedor, li);
    lista.appendChild(li);
  });
}

async function seleccionarProveedor(idProv, elementoLi) {
  proveedorSeleccionado = idProv;
  marcarSeleccion(elementoLi);
  cargarArticulosProveedor(idProv);
}

function marcarSeleccion(liSeleccionado) {
  document.querySelectorAll('#listaProveedoresActivos li').forEach(li => {
    li.classList.remove('selected');
  });
  liSeleccionado.classList.add('selected');
}

async function cargarArticulosProveedor(idProv) {
  const tbody = document.querySelector('#tablaArticulosProveedor tbody');
  tbody.innerHTML = '';

  const res = await fetch(`http://localhost:5000/Proveedor/articulos-proveedor/${idProv}`);
  const data = await res.json();

  data.forEach(art => {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #ddd';
    tr.style.cursor = 'default';

    const predeterminadoTexto = art.predeterminado ? 'Sí' : 'No';
tr.innerHTML = `
  <td style="padding: 8px;">${art.idArticulo}</td>
  <td style="padding: 8px;">${art.nombreArticulo}</td>
  <td style="padding: 8px; text-align:right;">$${art.precioUnitario.toFixed(2)}</td>
  <td style="padding: 8px; text-align:right;">${art.tiempoEntregaDias}</td>
  <td style="padding: 8px; text-align:right;">$${art.costoPedido.toFixed(2)}</td>
  <td style="padding: 8px; text-align:center;">${predeterminadoTexto}</td>
`;
    tbody.appendChild(tr);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  cargarProveedoresActivos();
});
</script>
<?php endif; ?>