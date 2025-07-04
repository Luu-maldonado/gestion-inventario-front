<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'precio-art';

include __DIR__ . '/../../tabs/ventas-tabs.php';
?>

<?php if ($action === 'precio-art'): ?>

<h2>Gestión de Precios y Descuentos</h2>

<div class="precio-grid">
  <div class="articulos-lista">
    <h4>Artículos Activos</h4>
    <ul id="listaArticulos"></ul>
  </div>

  <div class="precio-detalles">
    <div class="detalle-caja">
      <h4 id="tituloPrecio">Precio unitario de venta actual</h4>
      <div id="valorPrecio">$0.000</div>
      <button class="boton-accion" onclick="abrirModalPrecio()">Modificar precio</button>
    </div>

    <div class="detalle-caja">
      <h4>Descuento actual por cantidad</h4>
      <div id="descuentoContainer"></div>
    </div>
  </div>
</div>

<div class="modal" id="modalPrecio">
  <div class="modal-content">
  <h4>Ingresar nuevo precio</h4>
  <input type="number" id="inputPrecioNuevo" min="0" step="0.001">
  <div class="acciones-modal">
    <button  class="boton-accion" onclick="cerrarModal('modalPrecio')">Cerrar</button>
    <button  class="boton-accion" onclick="guardarNuevoPrecio()">Guardar</button>
  </div>
  </div>
</div>

<div class="modal" id="modalDescuento">
  <div class="modal-content">
  <h4>Modificar / Crear descuento</h4>
  <label>Cantidad vendida</label>
  <input type="number" id="inputCantidadDesc" min="1" max="999999">
  <label>Porcentaje de descuento</label>
  <input type="number" id="inputPorcentajeDesc" min="0.01" max="99.99" step="0.01">
  <div style="text-align: right;">
    <button class="boton-accion" onclick="cerrarModal('modalDescuento')">Volver</button>
    <button class="boton-accion" onclick="guardarDescuento()">Guardar</button>
  </div>
  </div>
</div>

<script>
let articuloSeleccionado = null;
let esNuevoDescuento = false;

async function cargarArticulos() {
  try {
    const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
    const data = await res.json();
    const ul = document.getElementById('listaArticulos');
    ul.innerHTML = '';
    data.forEach(art => {
      const li = document.createElement('li');
      li.className = 'articulo-item';
      li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
      li.onclick = () => seleccionarArticulo(art, li);
      ul.appendChild(li);
    });
  } catch (err) {
    alert("Error al cargar artículos: " + err.message);
  }
}

function seleccionarArticulo(art, liEl) {
  articuloSeleccionado = art;
  document.querySelectorAll('.articulo-item').forEach(el => el.classList.remove('selected'));
  liEl.classList.add('selected');
  document.getElementById('tituloPrecio').textContent = `Precio unitario de venta actual para el artículo ${art.nombreArticulo}`;
  cargarPrecio();
  cargarDescuento();
}

async function cargarPrecio() {
  try {
    const res = await fetch(`http://localhost:5000/api/Ventas/ver-precio-venta-articulo/${articuloSeleccionado.idArticulo}`);
    const data = await res.json();
    if (res.ok) {
      document.getElementById('valorPrecio').textContent = `$${(data.precioVenta || 0).toFixed(3)}`;
    } else {
      alert(data.error || data.mensaje);
    }
  } catch (err) {
    alert("Error al obtener precio: " + err.message);
  }
}

function abrirModalPrecio() {
  document.getElementById('inputPrecioNuevo').value = '';
  document.getElementById('modalPrecio').style.display = 'block';
}

function cerrarModal(id) {
  document.getElementById(id).style.display = 'none';
}

async function guardarNuevoPrecio() {
  const precio = parseFloat(document.getElementById('inputPrecioNuevo').value);
  if (!(precio > 0)) return alert("El precio debe ser un número positivo.");

  try {
    const res = await fetch('http://localhost:5000/api/Ventas/precio-venta-art', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ idArticulo: articuloSeleccionado.idArticulo, precioVentaArt: precio })
    });
    const data = await res.json();
    if (res.ok) {
      alert(data.mensaje);
      cerrarModal('modalPrecio');
      cargarPrecio();
    } else {
      alert(data.error || data.mensaje);
    }
  } catch (err) {
    alert("Error al guardar precio: " + err.message);
  }
}

async function cargarDescuento() {
  const cont = document.getElementById('descuentoContainer');
  cont.innerHTML = '';
  try {
    const res = await fetch(`http://localhost:5000/DescuentoArticulo/descuento-vigente/${articuloSeleccionado.idArticulo}`);
    const data = await res.json();
    if (res.ok) {
      const d = data.descuento;
      esNuevoDescuento = false;
      cont.innerHTML = `
        <p>Cantidad: <strong>${d.cantidadDesc}</strong></p>
        <p>Porcentaje: <strong>${d.porcentajeDesc}%</strong></p>
        <button class="boton-accion" onclick="abrirModalModDesc(${d.cantidadDesc}, ${d.porcentajeDesc})">Modificar descuento</button>
        <button class="boton-accion" onclick="eliminarDescuento()">Eliminar</button>
      `;
    } else {
      throw new Error(data.error || data.mensaje);
    }
  } catch (err) {
    esNuevoDescuento = true;
    cont.innerHTML = `
      <p>No hay descuento vigente</p>
      <button class="boton-accion" onclick="abrirModalModDesc()">Crear descuento</button>
    `;
  }
}

function abrirModalModDesc(cant = '', porc = '') {
  esNuevoDescuento = (cant === '' || porc === '');
  document.getElementById('inputCantidadDesc').value = cant;
  document.getElementById('inputPorcentajeDesc').value = porc;
  const modal = document.getElementById('modalDescuento');
  modal.style.display = 'flex'; // O 'block' según tu estructura
  modal.style.position = 'fixed';
  modal.style.top = '0';
  modal.style.left = '0';
  modal.style.width = '100%';
  modal.style.height = '100%';
  modal.style.backgroundColor = 'rgba(0,0,0,0.4)';
  modal.style.justifyContent = 'center';
  modal.style.alignItems = 'center';
  modal.style.zIndex = '9999';
  // ✅ Estilos al contenido interno
  const modalContent = modal.querySelector('.modal-content');
  modalContent.style.background = '#111';
  modalContent.style.color = 'white';
  modalContent.style.padding = '2em';
  modalContent.style.borderRadius = '8px';
  modalContent.style.maxWidth = '500px';
  modalContent.style.width = '90%';
  modalContent.style.boxShadow = '0 4px 12px rgba(0,0,0,0.5)';
  modalContent.style.display = 'flex';
  modalContent.style.flexDirection = 'column';
  modalContent.style.gap = '2em';
   // ✅ Estilos a los inputs
  const inputs = modalContent.querySelectorAll('input[type="number"]');
  inputs.forEach(input => {
    input.style.width = '100%';
    input.style.padding = '0.5em';
    input.style.border = 'none';
    input.style.borderRadius = '6px';
    input.style.background = '#555';
    input.style.color = 'white';
  });

  // ✅ Estilos a los botones
  const buttons = modalContent.querySelectorAll('button');
  buttons.forEach(btn => {
    btn.style.backgroundColor = '#004444';
    btn.style.color = 'white';
    btn.style.border = 'none';
    btn.style.borderRadius = '4px';
    btn.style.padding = '0.5em 1em';
    btn.style.fontWeight = 'bold';
    btn.style.cursor = 'pointer';
    btn.style.transition = 'background-color 0.3s ease';
  });
 
}

async function guardarDescuento() {
  const cantidad = parseInt(document.getElementById('inputCantidadDesc').value);
  const porcentaje = parseFloat(document.getElementById('inputPorcentajeDesc').value);
  if (!(cantidad > 0 && cantidad <= 999999)) return alert("Cantidad no válida");
  if (!(porcentaje > 0 && porcentaje < 100)) return alert("Porcentaje debe ser entre 0 y 100");

  const dto = {
    idArticulo: articuloSeleccionado.idArticulo,
    cantidadDesc: cantidad,
    porcentajeDesc: porcentaje
  };

  const endpoint = esNuevoDescuento
    ? 'http://localhost:5000/DescuentoArticulo/crear'
    : 'http://localhost:5000/DescuentoArticulo/modificar';

  const method = esNuevoDescuento ? 'POST' : 'PUT';

  try {
    const res = await fetch(endpoint, {
      method: method,
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(dto)
    });
    const text = await res.text();
    if (res.ok) {
      alert(text);
      cerrarModal('modalDescuento');
      setTimeout(() => cargarDescuento(), 200); 
    } else {
      alert("❌ Error: " + text);
    }
  } catch (err) {
    alert("Error al guardar descuento: " + err.message);
  }
}

async function eliminarDescuento() {
  if (!confirm("¿Eliminar descuento vigente?")) return;
  try {
    const res = await fetch(`http://localhost:5000/DescuentoArticulo/baja-logica/${articuloSeleccionado.idArticulo}`, {
      method: 'DELETE'
    });
    const text = await res.text();
    if (res.ok) {
      alert(text);
      cargarDescuento();
    } else {
      alert(text);
    }
  } catch (err) {
    alert("Error al eliminar descuento: " + err.message);
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulos);
</script>

<?php endif; ?>