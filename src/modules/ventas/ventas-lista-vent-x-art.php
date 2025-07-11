<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'lista-vent-x-art';

include __DIR__ . '/../../tabs/ventas-tabs.php';
?>

<?php if ($action === 'lista-vent-x-art'): ?>

  <div class="contenedor-lista-ventas-art">
    <div class="columna">
      <h3>Artículos activos</h3>
      <ul id="listaArticulosActivos" class="lista-articulos"></ul>
    </div>

    <div class="columna">
      <h3>Ventas del artículo</h3>
      <div id="mensajeSinVentas" class="mensaje-vacio"></div>
      <table class="tabla-base">
        <thead>
          <tr>
            <th>N° Venta</th>
            <th>Cantidad Vendida</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody id="tablaVentasArticulo"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let articuloSeleccionado = null;

async function cargarArticulosActivos() {
  const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
  const data = await res.json();
  const lista = document.getElementById('listaArticulosActivos');
  lista.innerHTML = '';

  data.forEach(art => {
    const li = document.createElement('li');
    li.className = 'articulo-item';
    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
    li.onclick = () => seleccionarArticulo(art.idArticulo, li);
    lista.appendChild(li);
  });
}

async function seleccionarArticulo(idArticulo, liElemento) {
  articuloSeleccionado = idArticulo;

  document.querySelectorAll('.articulo-item').forEach(li => li.classList.remove('selected'));
  liElemento.classList.add('selected');

  const res = await fetch(`http://localhost:5000/api/Ventas/art-vent/${idArticulo}`);
  const data = await res.json();

  const tabla = document.getElementById('tablaVentasArticulo');
  tabla.innerHTML = '';
  const mensaje = document.getElementById('mensajeSinVentas');

  if (data.length === 0) {
    mensaje.textContent = 'Este artículo no tiene ventas registradas.';
  } else {
    mensaje.textContent = '';
    data.forEach(venta => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${venta.nVenta}</td>
        <td>${venta.cantidadVendida}</td>
        <td>$${venta.subtotal.toFixed(2)}</td>
      `;
      tabla.appendChild(tr);
    });
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulosActivos);
</script>
<?php endif; ?>