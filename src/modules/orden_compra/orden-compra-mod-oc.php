<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'mod-oc';

include __DIR__ . '/../../tabs/orden-compra-tabs.php';
?>

<?php if ($action === 'mod-oc'): ?>

<div class="contenedor-ordenes-compra">

  <div class="contenedor-flex">
    
    <div class="columna">
      <h3>Órdenes Pendientes</h3>
      <ul id="listaOrdenesPendientes" class="lista-articulos"></ul>
    </div>

    <div class="columna">
      <h3>Detalles de Orden</h3>
      <div class="header-detalles-orden">
        <div id="infoProveedorActual" class="info-proveedor"></div>
        <button id="btnCambiarProveedor" type="button" class="boton-accion " disabled>Cambiar proveedor</button>
      </div>
      <form id="formDetallesOrden">
        <table class="tabla-base">
          <thead>
            <tr>
              <th>Nombre Artículo</th>
              <th>Cantidad</th>
              <th>Subtotal</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tablaDetallesEditar"></tbody>
        </table>
        <button id="btnGuardarCambios" type="submit" class="boton-accion" disabled>Guardar cambios</button>
      </form>
      <div id="mensajeAdvertencias"  class="mensaje-advertencias"></div>
    </div>

    <div class="columna">
      <h3>Artículos del proveedor</h3>
      <ul id="listaArticulosProveedor" class="lista-articulos"></ul>
    </div>

  </div>
</div>

<script>
let ordenesPendientes = [], ordenSeleccionada = null;
let detallesOrdenLocal = [], articulosFaltantesLocal = [], articulosFaltantesOriginal = [];

async function cargarOrdenesPendientes() {
  const res = await fetch('http://localhost:5000/OrdenCompra/lista-ordenes');
  const data = await res.json();
  ordenesPendientes = data.filter(o => o.estado.toLowerCase() === 'pendiente');
  const ul = document.getElementById('listaOrdenesPendientes');
  ul.innerHTML = '';
  ordenesPendientes.forEach(oc => {
    const li = document.createElement('li');
    li.textContent = `#${oc.nOrdenCompra} - ${oc.proveedor}`;
    li.classList.add('articulo-item'); 
    li.className="articulo-item";
    li.onclick = () => seleccionarOrden(oc, li); 
    ul.appendChild(li);
  });
  document.getElementById('btnCambiarProveedor').disabled = true;
  document.getElementById('btnGuardarCambios').disabled = true;
}

async function seleccionarOrden(orden, liSeleccionado) {
  ordenSeleccionada = orden;
  document.getElementById('btnCambiarProveedor').disabled = false;
  document.getElementById('btnGuardarCambios').disabled = false;
  document.querySelectorAll('#listaOrdenesPendientes li.articulo-item').forEach(li => {
    li.classList.remove('selected');
  });

  liSeleccionado.classList.add('selected');
  const [detRes, artRes, valRes] = await Promise.all([
    fetch(`http://localhost:5000/OrdenCompra/detalles-orden/${orden.nOrdenCompra}`),
    fetch(`http://localhost:5000/OrdenCompra/proveedor/${orden.nOrdenCompra}/articulos-no-inc/${orden.idProveedor}`),
    fetch(`http://localhost:5000/OrdenCompra/validar-detalles/${orden.nOrdenCompra}`)
  ]);
  detallesOrdenLocal = await detRes.json();
  const articulosBase = await artRes.json();
  articulosFaltantesOriginal = articulosBase.slice();

  const idsDetalles = detallesOrdenLocal.map(d => d.idArticulo);
  const mapeoDetalles = detallesOrdenLocal.reduce((map, d) => {
    map[d.idArticulo] = d.nombreArticulo;
    return map;
  }, {});

  const articulosAdicionales = idsDetalles
    .filter(id => !articulosBase.some(a => a.idArticulo === id))
    .map(id => ({ idArticulo: id, nombreArticulo: mapeoDetalles[id], seleccionado: true }));

  articulosFaltantesLocal = [...articulosBase.map(a => ({ ...a, seleccionado: idsDetalles.includes(a.idArticulo) })), ...articulosAdicionales];

  const validacion = await valRes.json();
  renderizarTodo();
  actualizarEtiquetaProveedor();
  document.getElementById('btnGuardarCambios').style.display = 'inline-block';

  let advertencias = [...(validacion.advertenciasOC_oc || []), ...(validacion.advertenciasOC_pp || [])];
  if (advertencias.length > 0) {
    document.getElementById('mensajeAdvertencias').innerHTML = advertencias.map(a => `⚠️ ${a}`).join('<br>');
  } else {
    document.getElementById('mensajeAdvertencias').innerHTML = '✅ No se encontraron advertencias para esta orden.';
  }
}

function renderizarTodo() {
  const tabla = document.getElementById('tablaDetallesEditar');
  tabla.innerHTML = '';
detallesOrdenLocal.forEach(d => {
  const tr = document.createElement('tr');
  const esArticuloPersistido = !articulosFaltantesOriginal.some(a => a.idArticulo === d.idArticulo);
  const etiquetaVerde = esArticuloPersistido ? '<div class="articulo-guardado">(guardado actualmente en la orden)</div>' : '';
  tr.innerHTML = `
    <td>${d.nombreArticulo}${etiquetaVerde}</td>
    <td class="editable" contenteditable="true" onfocus="guardarValorOriginal(this)" onblur="actualizarCantidad(this, ${d.idArticulo})">${d.cantidad}</td>
    <td>$${d.subTotal.toFixed(2)}</td>
    <td><button class="boton-accion" onclick="eliminarDetalle(${d.idArticulo})" ${detallesOrdenLocal.length <= 1 ? 'disabled' : ''}>❌ Eliminar</button></td>
  `;
  tabla.appendChild(tr);
});

  const ul = document.getElementById('listaArticulosProveedor');
  ul.innerHTML = '';
  articulosFaltantesLocal.forEach(a => {
    const li = document.createElement('li');
    li.innerHTML = `${a.nombreArticulo}`;
    li.style = 'padding: 0.5em; cursor: pointer;';
    if (a.seleccionado) li.classList.add('disabled-articulo');
    li.onclick = () => { if (!a.seleccionado) agregarArticuloNuevo(a.idArticulo); };
    ul.appendChild(li);
  });
}

function guardarValorOriginal(td) {
  td.dataset.original = td.innerText.trim();
}

function actualizarCantidad(td, idArticulo) {
  const nuevaCantidad = parseInt(td.innerText.trim());
  if (isNaN(nuevaCantidad) || nuevaCantidad <= 0) {
    td.innerText = td.dataset.original;
    return;
  }
  const detalle = detallesOrdenLocal.find(d => d.idArticulo === idArticulo);
  if (detalle) {
    const precioUnitario = detalle.subTotal / detalle.cantidad;
    detalle.cantidad = nuevaCantidad;
    detalle.subTotal = Math.round(precioUnitario * nuevaCantidad * 100) / 100;
  }
}

async function agregarArticuloNuevo(idArticulo) {
  try {
    const res = await fetch(`http://localhost:5000/MaestroArticulos/calcular-cantidad-subtotal/${idArticulo}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Error desconocido');
    const art = articulosFaltantesLocal.find(a => a.idArticulo === idArticulo);
    detallesOrdenLocal.push({
      idArticulo: art.idArticulo,
      nombreArticulo: art.nombreArticulo,
      cantidad: data.cantidad,
      subTotal: data.subtotal
    });
    art.seleccionado = true;
    if (data.aviso) alert('⚠️ ' + data.aviso);
    renderizarTodo();
  } catch (err) {
    alert("❌ Error al agregar artículo: " + err.message);
  }
}

function eliminarDetalle(idArticulo) {
  if (detallesOrdenLocal.length <= 1) return;
  detallesOrdenLocal = detallesOrdenLocal.filter(d => d.idArticulo !== idArticulo);
  let art = articulosFaltantesLocal.find(a => a.idArticulo === idArticulo);
  if (art) {
    art.seleccionado = false;
  } else {
    const original = articulosFaltantesOriginal.find(a => a.idArticulo === idArticulo);
    if (original) {
      articulosFaltantesLocal.push({ ...original, seleccionado: false });
    } else {
      articulosFaltantesLocal.push({ idArticulo: idArticulo, nombreArticulo: '[artículo eliminado]', seleccionado: false });
    }
  }
  renderizarTodo();
}

document.getElementById('formDetallesOrden').addEventListener('submit', async e => {
  e.preventDefault();
  if (!ordenSeleccionada) return;
  const articulos = detallesOrdenLocal.map(d => ({ idArticulo: d.idArticulo, cantidad: d.cantidad }));
  try {
    const res = await fetch('http://localhost:5000/OrdenCompra/modificar-orden', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nOrdenCompra: ordenSeleccionada.nOrdenCompra,
        idProveedor: ordenSeleccionada.idProveedor,
        articulos
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'No se pudo modificar');
    let advertencias = [...(data.advertenciasOC_oc || []), ...(data.advertenciasOC_pp || [])];
    if (advertencias.length > 0) {
      document.getElementById('mensajeAdvertencias').innerHTML = advertencias.map(a => `⚠️ ${a}`).join('<br>');
    } else {
      document.getElementById('mensajeAdvertencias').innerHTML = '✅ No se encontraron advertencias para esta orden.';
    }
    alert(data.mensajeOC || "Orden modificada correctamente");
    location.reload();
  } catch (err) {
    alert("❌ Error al guardar: " + err.message);
  }
});

document.addEventListener('DOMContentLoaded', cargarOrdenesPendientes);

function actualizarEtiquetaProveedor() {
  if (!ordenSeleccionada) return;
  const etiqueta = document.getElementById('infoProveedorActual');
  etiqueta.textContent = `Proveedor actual: [${ordenSeleccionada.idProveedor}] ${ordenSeleccionada.proveedor}`;
}

document.getElementById('btnCambiarProveedor').addEventListener('click', async () => {
  try {
    const res = await fetch('http://localhost:5000/Proveedor/activos');
    if (!res.ok) throw new Error("No se pudieron obtener proveedores activos");
    const proveedores = await res.json();

    const modalExistente = document.getElementById('modalCambiarProveedor');
    if (modalExistente) modalExistente.remove(); 

    const proveedoresFiltrados = proveedores.filter(p => p.idProveedor !== ordenSeleccionada.idProveedor);

    const modalWrapper = document.createElement('div');
    modalWrapper.id = 'modalCambiarProveedor';
    modalWrapper.className = 'modal';
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';

    modalContent.innerHTML = `
      <span class="close" onclick="document.getElementById('modalCambiarProveedor').remove()">&times;</span>
      <h3>Seleccionar nuevo proveedor</h3>
      <table class="tabla-base">
        <thead>
          <tr>
            <th>ID Proveedor</th>
            <th>Nombre</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="tablaProveedoresActivos"></tbody>
      </table>
    `;
  modalWrapper.appendChild(modalContent);
  document.body.appendChild(modalWrapper);
  modalWrapper.style.display = 'flex'; 

    const tbody = document.getElementById('tablaProveedoresActivos');
    proveedoresFiltrados.forEach(p => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${p.idProveedor}</td>
        <td>${p.nombreProveedor}</td>
        <td><button class="boton-accion btnSeleccionarProveedor" data-id="${p.idProveedor}" data-nombre="${encodeURIComponent(p.nombreProveedor)}">Seleccionar</button></td>
      `;
      tbody.appendChild(tr);
    });

    document.querySelectorAll('.btnSeleccionarProveedor').forEach(btn => {
      btn.onclick = () => {
        const id = parseInt(btn.dataset.id);
        const nombre = decodeURIComponent(btn.dataset.nombre);
        seleccionarNuevoProveedor(id, nombre);
      };
    });

  } catch (err) {
    alert("❌ Error al cargar proveedores: " + err.message);
  }
});

async function seleccionarNuevoProveedor(idProveedor, nombreProveedor) {
  if (!ordenSeleccionada) return;
  try {
    const res = await fetch(`http://localhost:5000/OrdenCompra/ordenCompra/${ordenSeleccionada.nOrdenCompra}/cambiar-proveedor/${idProveedor}`, {
      method: 'PUT'
    });

    const contentType = res.headers.get("content-type");
    const data = contentType && contentType.includes("application/json")
      ? await res.json()
      : await res.text();

    if (!res.ok) throw new Error(data.error || data || "No se pudo cambiar el proveedor");

    alert(data.mensaje || "Proveedor actualizado correctamente");
    ordenSeleccionada.idProveedor = idProveedor;
    ordenSeleccionada.proveedor = nombreProveedor;
    document.getElementById('modalCambiarProveedor').remove();
    actualizarEtiquetaProveedor();

    await seleccionarOrden(ordenSeleccionada, document.querySelector('.selected'));
  } catch (err) {
    alert("❌ Error al cambiar proveedor: " + err.message);
  }
}

</script>
<?php endif; ?>