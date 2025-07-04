<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'historico-est-prov';

include __DIR__ . '/../../tabs/proveedores-tabs.php';
?>

<?php if ($action === 'historico-est-prov'): ?>
  <div class="proveedor-articulos-container">

  <div class="columna">
    <h4>Proveedores</h4>
    <ul id="listaProveedores" class="lista-articulos"></ul>
  </div>

  <div class="columna">
    <h4>Histórico de Estados</h4>
    <table id="tablaHistorico" class="tabla-base">
      <thead>
        <tr>
          <th>Estado</th>
          <th>Fecha Inicio</th>
          <th>Fecha Fin</th>
        </tr>
      </thead>
      <tbody>

      </tbody>
    </table>
  </div>

</div>

<script>
let proveedorSeleccionado = null;

async function cargarProveedores() {
  const lista = document.getElementById('listaProveedores');
  lista.innerHTML = '';
  try {
    const res = await fetch('http://localhost:5000/Proveedor/proveedores-sist');
    if (!res.ok) throw new Error('Error cargando proveedores');
    const data = await res.json();
    data.forEach(p => {
      const li = document.createElement('li');
      li.textContent = `#${p.idProveedor} - ${p.nombreProveedor}`;
      li.style.cursor = 'pointer';
      li.classList.add('articulo-item'); 

      li.onclick = () => seleccionarProveedor(p.idProveedor, li);

      lista.appendChild(li);
    });
  } catch (err) {
    alert('Error al cargar proveedores: ' + err.message);
  }
}

async function seleccionarProveedor(idProveedor, elementoLi) {
  proveedorSeleccionado = idProveedor;
  document.querySelectorAll('#listaProveedores li').forEach(li => li.classList.remove('selected'));
  elementoLi.classList.add('selected');
  await cargarHistorico(idProveedor);
}

async function cargarHistorico(idProveedor) {
  const tbody = document.querySelector('#tablaHistorico tbody');
  tbody.innerHTML = '';

  try {
    const res = await fetch(`http://localhost:5000/Proveedor/${idProveedor}/historial`);
    if (!res.ok) throw new Error('Error al obtener histórico');
    const data = await res.json();

    if (data.length === 0) {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td colspan="3">No hay historial para este proveedor</td>`;
      tbody.appendChild(tr);
      return;
    }

    data.forEach(est => {
      const tr = document.createElement('tr');

      const fechaInicio = est.fechaIEstadoProveedor ? new Date(est.fechaIEstadoProveedor).toLocaleDateString() : '';
      const fechaFin = est.fechaFEstadoProveedor ? new Date(est.fechaFEstadoProveedor).toLocaleDateString() : '';

      tr.innerHTML = `
        <td>${est.nombreEstado}</td>
        <td>${fechaInicio}</td>
        <td>${fechaFin}</td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    alert('Error al cargar histórico: ' + err.message);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  cargarProveedores();
});
</script>
<?php endif; ?>