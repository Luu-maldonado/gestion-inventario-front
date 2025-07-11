<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'gestion-oc';

include __DIR__ . '/../../tabs/orden-compra-tabs.php';
?>

<?php if ($action === 'gestion-oc'): ?>

<div class="contenedor-ordenes-compra">

  <div class="header-ordenes">
    <h2>Gestión de Órdenes de Compra</h2>
    <button id="btnGenerarOC"  class="boton-accion">
     Generar Orden Compra
    </button>
  </div>

  <div class="tabla-wrapper">
    <table class="tabla-base">
      <thead>
        <tr>
          <th>N° Orden</th>
          <th>Proveedor</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th>Total a Pagar</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tablaOrdenesCompra"></tbody>
    </table>
  </div>

</div>

<div id="modalDetalles"  class="modal">
  <div class="modal-content">
    <span class="close" onclick="cerrarModalDetalles()">&times;</span>
    <h3>Detalles de Orden de Compra</h3>
    <table class="tabla-base">
      <thead>
        <tr>
          <th>ID Articulo</th>
          <th>Nombre Articulo</th>
          <th>Cantidad</th>
          <th>Precio Unitario</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody id="tablaDetallesOrden"></tbody>
    </table>
  </div>
</div>

<div id="modalGenerarOC" class="modal">
  <div class="modal-content">
  <span class="close" onclick="cerrarModalOC()">&times;</span>
  <h3>Seleccionar artículo para generar orden de compra</h3>
  <table class="tabla-base">
    <thead>
      <tr>
        <th>ID Artículo</th>
        <th>Nombre Artículo</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody id="tablaArticulosActivos"></tbody>
  </table>
  </div>
</div>

<div id="modalAdvertenciasOC" class="modal">
  <div class="modal-content">
    <span class="close" onclick="cerrarAdvertenciasModal()">&times;</span>
    <h3>Advertencias durante la generación de la orden</h3>
    <ul id="listaAdvertenciasOC"></ul>
    <div>
      <button class="boton-accion" onclick="cerrarAdvertenciasModal()">Cerrar</button>
    </div>
  </div>
</div>

<script>
async function cargarOrdenesCompra() {
  const res = await fetch('http://localhost:5000/OrdenCompra/lista-ordenes');
  const data = await res.json();
  const tbody = document.getElementById('tablaOrdenesCompra');
  tbody.innerHTML = '';

  data.forEach(oc => {
    const advertenciaIcono = oc.advertencia
      ? `<span title="Existen advertencias en detalles de esta orden de compra" style="color:orange; font-size: 1.2em;">&#9888;</span> `
      : '';

const tr = document.createElement('tr');
tr.innerHTML = `
  <td>${oc.nOrdenCompra}&nbsp;${advertenciaIcono}</td>
  <td>${oc.proveedor}</td>
  <td>${oc.estado}</td>
  <td>${new Date(oc.fechaOrden).toLocaleDateString()}</td>
  <td>$${oc.totalPagar.toFixed(2)}</td>
  <td class="acciones-col">
    <button class="boton-accion" onclick="verDetalles(${oc.nOrdenCompra})">📓 Ver detalles</button>
    <button class="boton-accion" onclick="cambiarEstado(${oc.nOrdenCompra})">➡️ Cambiar estado</button>
  </td>`;

    tbody.appendChild(tr);
  });
}

async function verDetalles(nOrdenCompra) {
  try {
    const res = await fetch(`http://localhost:5000/OrdenCompra/detalles-orden/${nOrdenCompra}`);
    if (!res.ok) throw new Error("Error al obtener detalles");
    const data = await res.json();
    const tbody = document.getElementById('tablaDetallesOrden');
    tbody.innerHTML = '';

    data.forEach(det => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${det.idArticulo}</td>
        <td>${det.nombreArticulo}</td>
        <td>${det.cantidad}</td>
        <td>$${det.precioUnitario.toFixed(3)}</td>
        <td>$${det.subTotal.toFixed(3)}</td>`;
      tbody.appendChild(tr);

      if (det.advertencia && det.advertencia.trim() !== "") {
        const advTr = document.createElement('tr');
        advTr.innerHTML = `
          <td colspan="5" style="background-color: #ffe5d1; color: #a94442; border-left: 5px solid orange;">
            ⚠️ ${det.advertencia}
          </td>`;
        tbody.appendChild(advTr);
      }

      const espacioTr = document.createElement('tr');
      espacioTr.innerHTML = `<td colspan="5" style="height: 8px;"></td>`;
      tbody.appendChild(espacioTr);
    });

    document.getElementById('modalDetalles').style.display = 'block';
  } catch (err) {
    alert("Error: " + err.message);
  }
}

function cerrarModalDetalles() {
  document.getElementById('modalDetalles').style.display = 'none';
}

function irAEdicion(id) {
  location.href = `index.php?mod=ordenes_compra&action=mod_orden_compra&id=\${id}`;
}

function cambiarEstado(id) {
  alert("Funcionalidad de cambio de estado aún no implementada");
}

function cerrarModalOC() {
  document.getElementById('modalGenerarOC').style.display = 'none';
}

document.getElementById('btnGenerarOC').addEventListener('click', async () => {
  document.getElementById('modalGenerarOC').style.display = 'block';
  const tbody = document.getElementById('tablaArticulosActivos');
  tbody.innerHTML = '';

  try {
    const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
    const articulos = await res.json();

    articulos
    .filter(art => art.cgi != null && art.cgi !== 0)
    .forEach(art => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${art.idArticulo}</td>
        <td>${art.nombreArticulo}</td>
        <td><button onclick="generarOCConArticulo(${art.idArticulo})">Seleccionar</button></td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    tbody.innerHTML = '<tr><td colspan="3">Error al cargar artículos</td></tr>';
  }
});

async function generarOCConArticulo(idArticulo) {
  try {
    const provRes = await fetch(`http://localhost:5000/Proveedor/articulo/prov-pred/${idArticulo}`);
    if (!provRes.ok) throw new Error(await provRes.text());
    const idProveedor = await provRes.json();

    const body = {
      idProveedor: idProveedor,
      articulos: [{ idArticulo }]
    };

    const res = await fetch('http://localhost:5000/OrdenCompra/generar-orden', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const contentType = res.headers.get("content-type") || "";
    const data = contentType.includes("application/json") ? await res.json() : await res.text();

    if (res.ok) {
      alert(data.mensajeOC || "Orden generada correctamente");
      cerrarModalOC();
      cargarOrdenesCompra();

      const advertencias = [...(data.advertenciasOC_pp || []), ...(data.advertenciasOC_oc || [])];
      if (advertencias.length > 0) {
        mostrarAdvertenciasModal(advertencias);
      }

    } else {
      alert("Error: " + (data.error || data));
    }
  } catch (err) {
    alert("Error: " + err.message);
  }
}

async function cambiarEstado(nOrdenCompra) {
  try {
    const res = await fetch(`http://localhost:5000/OrdenCompra/lista-ordenes`);
    const ordenes = await res.json();
    const orden = ordenes.find(o => o.nOrdenCompra === nOrdenCompra);
    if (!orden) return alert("Orden no encontrada.");

    const modal = document.createElement('div');
    modal.id = 'modalCambioEstado';
    modal.style = 'position: fixed; top: 20%; left: 25%; width: 50%; background:#111; border: 1px solid #ccc; padding: 1em; z-index: 1000; box-shadow: 0 0 10px rgba(0,0,0,0.3);';

    const cerrarBtn = document.createElement('span');
    cerrarBtn.innerHTML = '&times;';
    cerrarBtn.style = 'float: right; cursor: pointer; font-size: 2em;';
    cerrarBtn.onclick = cerrarModalEstado;

    const titulo = document.createElement('h3');
    titulo.innerText = `Estado actual de la orden: ${orden.estado}`;

    const acciones = document.createElement('div');
    acciones.style = 'margin-top: 15px; display: flex; flex-direction: column; gap: 15px;';

    async function obtenerContenidoRespuesta(res) {
      const contentType = res.headers.get("content-type");
      return contentType && contentType.includes("application/json")
        ? await res.json()
        : await res.text();
    }

    if (orden.estado.toLowerCase() === 'pendiente') {
      const btnConfirmar = document.createElement('button');
      btnConfirmar.innerText = 'Cambiar estado: pendiente ⟶ enviada';
      btnConfirmar.style = 'background-color:rgb(0, 78, 88); color: white; font-weight: bold; padding: 0.5em 1em; border: none; cursor: pointer;';
      btnConfirmar.onclick = async () => {
        try {
          const res = await fetch(`http://localhost:5000/OrdenCompra/confirmar-orden/${nOrdenCompra}`, { method: 'POST' });
          const data = await obtenerContenidoRespuesta(res);
          alert(data.mensaje || data || "Orden de compra confirmada.");
          cerrarModalEstado();
          cargarOrdenesCompra();
        } catch (err) {
          alert("Error al confirmar: " + err.message);
        }
      };
      acciones.appendChild(btnConfirmar);

      const btnCancelar = document.createElement('button');
      btnCancelar.innerText = 'Cancelar orden de compra';
      btnCancelar.style = 'background-color: red; color: white; font-weight: bold; padding: 0.5em 1em; border: none; cursor: pointer;';
      btnCancelar.onclick = async () => {
        if (!confirm("¿Está seguro que desea cancelar esta orden de compra?")) return;
        try {
          const res = await fetch(`http://localhost:5000/OrdenCompra/cancelar/${nOrdenCompra}`, { method: 'POST' });
          const data = await obtenerContenidoRespuesta(res);
          alert(data.mensaje || data || "Orden cancelada exitosamente");
          cerrarModalEstado();
          cargarOrdenesCompra();
        } catch (err) {
          alert("Error al cancelar: " + err.message);
        }
      };
      acciones.appendChild(btnCancelar);

    } else if (orden.estado.toLowerCase() === 'enviada') {
      const btnRegistrar = document.createElement('button');
      btnRegistrar.innerText = 'Cambiar estado: enviada ⟶ archivada (registrar entrada de artículos)';
      btnRegistrar.style = 'background-color:rgb(0, 107, 27); color: white; font-weight: bold; padding: 0.5em 1em; border: none; cursor: pointer;';
      btnRegistrar.onclick = async () => {
        try {
          const res = await fetch(`http://localhost:5000/OrdenCompra/registrar-entrada/${nOrdenCompra}`, { method: 'POST' });
          const data = await obtenerContenidoRespuesta(res);
          alert(data.mensaje || data || "Entrada de artículos registrada correctamente");
          cerrarModalEstado();
          cargarOrdenesCompra();
        } catch (err) {
          alert("Error al registrar entrada: " + err.message);
        }
      };
      acciones.appendChild(btnRegistrar);

    } else {
      const aviso = document.createElement('p');
      aviso.innerText = "Esta orden ya se encuentra archivada o cancelada. No es posible modificar su estado.";
      aviso.style = "color: gray; font-style: italic;";
      acciones.appendChild(aviso);
    }

    modal.appendChild(cerrarBtn);
    modal.appendChild(titulo);
    modal.appendChild(acciones);
    document.body.appendChild(modal);

  } catch (err) {
    alert("Error al procesar estado: " + err.message);
  }
}

function cerrarModalEstado() {
  const modal = document.getElementById('modalCambioEstado');
  if (modal) modal.remove();
}

function mostrarAdvertenciasModal(advertencias) {
  const lista = document.getElementById('listaAdvertenciasOC');
  lista.innerHTML = '';
  advertencias.forEach(msg => {
    const li = document.createElement('li');
    li.innerHTML = `⚠️ ${msg}`;
    li.style.marginBottom = '0.5em';
    lista.appendChild(li);
  });
  document.getElementById('modalAdvertenciasOC').style.display = 'block';
}

function cerrarAdvertenciasModal() {
  document.getElementById('modalAdvertenciasOC').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', cargarOrdenesCompra);
</script>
<?php endif; ?>