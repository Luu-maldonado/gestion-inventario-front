<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'abm-prov';

include __DIR__ . '/../../tabs/proveedores-tabs.php';
?>

<?php if ($action === 'abm-prov'): ?>
    <h3>Gestión de Proveedores</h3>
<div class="table-header">
  <div class="acciones">
    <button onclick="abrirModalProveedor()" class="boton-accion">➕ Crear proveedor</button>
  </div>
</div>

<div id="modalCrearProveedorCompleto" class="modal">
  <span class="close" onclick="cerrarModalCrearProveedorCompleto()">&times;</span>
  <div class="modal-content alta-proveedor">
    

    <div class="columna columna-datos-proveedor">
      <h4>Datos del proveedor</h4>
      <form id="formNuevoProveedor">
        <label>Nombre:</label><br>
        <input type="text" name="nombreProveedor" required><br>
        <label>Dirección:</label><br>
        <input type="text" name="direccion" required><br>
        <label>Email:</label><br>
        <input type="email" name="mail" required><br>
        <label>Teléfono:</label><br>
        <input type="text" name="telefono" required><br><br>
        <button class="boton-accion" type="submit">Guardar proveedor con artículos</button>
      </form>
    </div>

    <div class="columna columna-lista-articulos">
      <h4>Artículos activos</h4>
      <ul class="articulo-item" id="listaArticulosAsignables"></ul>
    </div>

<div class="columna">
  <h4>Datos del artículo seleccionado</h4>
  <div id="detalleArticuloSeleccionado"></div>
</div>

    <div class="columna">
      <h4>Artículos asignados</h4>
      <div class="articulo-resumen" id="articulosAsignados"></div>
    </div>
  </div>
</div>

    <table class="tabla-base">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="bodyProveedores">
        </tbody>
    </table>

    <div id="modalProveedor" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="cerrarModalProveedor()">&times;</span>
            <h3 id="modalProveedorTitulo">Crear nuevo proveedor</h3>
            <form id="formProveedor">
                <input type="hidden" id="idProveedor" name="idProveedor" />
                <label>Nombre:</label><br>
                <input type="text" name="nombreProveedor" required><br>
                <label>Dirección:</label><br>
                <input type="text" name="direccion"><br>
                <label>Email:</label><br>
                <input type="email" name="mail"><br>
                <label>Teléfono:</label><br>
                <input type="text" name="telefono"><br><br>
                <input type="submit" value="Guardar proveedor">
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        const form = document.getElementById('formProveedor');
        const modal = document.getElementById('modalProveedor');

        window.cerrarModalProveedor = () => {
            modal.style.display = 'none';
        };

        window.editarProveedor = (proveedor) => {
            modal.style.display = 'block';
            document.getElementById('modalProveedorTitulo').textContent = "Editar proveedor";
            form.idProveedor.value = proveedor.idProveedor;
            form.nombreProveedor.value = proveedor.nombreProveedor;
            form.direccion.value = proveedor.direccion;
            form.mail.value = proveedor.mail;
            form.telefono.value = proveedor.telefono;
            form.querySelector('input[type="submit"]').value = 'Guardar cambios';
        };

window.eliminarProveedor = async (id) => {
  if (!confirm("¿Eliminar proveedor?")) return;

  try {
    const res = await fetch(`http://localhost:5000/Proveedor/eliminar/${id}`, { method: 'DELETE' });

    if (res.ok) {
      alert("Proveedor eliminado correctamente");
      cargarProveedores(); // Refresca lista
    } else {
      const contentType = res.headers.get("content-type");
      let errorMsg = "Error al eliminar proveedor.";

      if (contentType && contentType.includes("application/json")) {
        const body = await res.json();
        errorMsg = body?.error || JSON.stringify(body);
      } else {
        errorMsg = await res.text();
      }

      alert("Error al eliminar: " + errorMsg);
    }
  } catch (err) {
    alert("Error de red: " + err.message);
  }
};

window.suspenderProveedor = async (id) => {
  if (!confirm("¿Suspender proveedor?")) return;

  try {
    const res = await fetch(`http://localhost:5000/Proveedor/suspender/${id}`, { method: 'PUT' });

    if (res.ok) {
      alert("Proveedor suspendido correctamente");
      cargarProveedores();
    } else {
      const contentType = res.headers.get("content-type");
      let errorMsg = "Error al suspender proveedor.";

      if (contentType && contentType.includes("application/json")) {
        const body = await res.json();
        errorMsg = body?.error || JSON.stringify(body);
      } else {
        errorMsg = await res.text();
      }

      alert("Error al suspender: " + errorMsg);
    }
  } catch (err) {
    alert("Error de red: " + err.message);
  }
};

form.addEventListener('submit', async e => {
  e.preventDefault();

  const isEditar = !!form.idProveedor.value;
  const idProveedor = form.idProveedor.value;

  const data = {
    nombreProveedor: form.nombreProveedor.value,
    direccion: form.direccion.value,
    mail: form.mail.value,
    telefono: form.telefono.value
  };

  const url = isEditar
    ? `http://localhost:5000/Proveedor/actualizar/${idProveedor}`
    : 'http://localhost:5000/Proveedor/crear';

  const method = isEditar ? 'PUT' : 'POST';

  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    if (res.ok) {
      alert(isEditar ? "Proveedor actualizado" : "Proveedor creado");
      cerrarModalProveedor();
      cargarProveedores();
    } else {
      const msg = await res.text();
      alert("Error: " + msg);
    }
  } catch (err) {
    alert("Error de red");
  }
});

    cargarProveedores();
    });
            async function cargarProveedores() {
                const tbody = document.getElementById('bodyProveedores');
            try {
                const resp = await fetch('http://localhost:5000/Proveedor/activos');
                const proveedores = await resp.json();

                tbody.innerHTML = '';
                proveedores.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${p.idProveedor}</td>
                        <td>${p.nombreProveedor}</td>
                        <td>${p.direccion || ''}</td>
                        <td>${p.mail || ''}</td>
                        <td>${p.telefono || ''}</td>
                        <td>
                            <button onclick='editarProveedor(${JSON.stringify(p)})'>✏️</button>
                            <button onclick='eliminarProveedor(${p.idProveedor})' style="color:red;">❌</button>
                            <button onclick='suspenderProveedor(${p.idProveedor})' style="color:orange;">S</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="6">Error al cargar proveedores</td></tr>';
            }
        }

let articulosActivosLocal = [], listaAgregarLocal = [];

function cerrarModalCrearProveedorCompleto() {
  document.getElementById('modalCrearProveedorCompleto').style.display = 'none';
}

async function abrirModalProveedor() {
  document.getElementById('modalCrearProveedorCompleto').style.display = 'block';
  document.getElementById('formNuevoProveedor').reset();
  articulosActivosLocal = [];
  listaAgregarLocal = [];
  await cargarArticulosActivos();
  actualizarListaAsignados();
  document.getElementById('detalleArticuloSeleccionado').innerHTML = '';
}

async function cargarArticulosActivos() {
  try {
    const resp = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
    const articulos = await resp.json();
    articulosActivosLocal = articulos.map(a => ({ ...a, seleccionado: false }));
    renderizarListaActivos();
  } catch {
    document.getElementById('listaArticulosAsignables').innerHTML = '<li>Error al cargar artículos</li>';
  }
}

function renderizarListaActivos() {
  const lista = document.getElementById('listaArticulosAsignables');
  lista.innerHTML = '';
  articulosActivosLocal.forEach(art => {
    const li = document.createElement('li');
    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
    li.className = 'articulo-item';
    if (art.seleccionado) li.classList.add('disabled');
    li.onclick = () => { if (!art.seleccionado) seleccionarArticulo(art, li); };
    lista.appendChild(li);
  });
}

function seleccionarArticulo(art, li) {
  document.querySelectorAll('#listaArticulosAsignables .articulo-item').forEach(el => el.classList.remove('selected'));
  li.classList.add('selected');

document.getElementById('detalleArticuloSeleccionado').innerHTML = `
  <form id="formArticuloTemp">
    <label>Precio Unitario:</label><input type="number" id="precio_${art.idArticulo}" step="0.01" required>
    <label>Tiempo de Entrega (días):</label><input type="number" id="tiempo_${art.idArticulo}" required>
    <label>Costo de Pedido:</label><input type="number" id="costo_${art.idArticulo}" step="0.01" required>
    <button class="boton-accion" type="button" onclick="confirmarAsignacionArticulo(${art.idArticulo})">➕ Asignar</button>
  </form>
`;
}

function confirmarAsignacionArticulo(idArticulo) {
  const precio = parseFloat(document.getElementById(`precio_${idArticulo}`).value);
  const tiempo = parseInt(document.getElementById(`tiempo_${idArticulo}`).value);
  const costo = parseFloat(document.getElementById(`costo_${idArticulo}`).value);

  if (isNaN(precio) || isNaN(tiempo) || isNaN(costo)) {
    alert("Todos los campos deben ser válidos.");
    return;
  }

  if (precio < 0.01 || precio > 999999 || costo < 0.01 || costo > 999999 || tiempo < 1 || tiempo > 999999 || !Number.isInteger(tiempo)) {
    alert("Valores fuera de rango permitido.");
    return;
  }

  const art = articulosActivosLocal.find(a => a.idArticulo === idArticulo);
  art.seleccionado = true;

  listaAgregarLocal.push({
    idArticulo,
    nombreArticulo: art.nombreArticulo,
    precioUnitario: precio,
    tiempoEntregaDias: tiempo,
    costoPedido: costo,
    predeterminado: false,
    fechaFinProveedorArticulo: null
  });

  actualizarListaAsignados();
  renderizarListaActivos();
  document.getElementById('detalleArticuloSeleccionado').innerHTML = '';
}

function actualizarListaAsignados() {
  const contenedor = document.getElementById('articulosAsignados');
  contenedor.innerHTML = '';
  listaAgregarLocal.forEach((a, index) => {
    const div = document.createElement('div');
    div.className = 'articulo-resumen';
    div.innerHTML = `
      <strong>${a.nombreArticulo}</strong><br>
      Precio: $${a.precioUnitario.toFixed(2)} | Tiempo: ${a.tiempoEntregaDias} días | Costo: $${a.costoPedido.toFixed(2)}
      <button onclick="eliminarArticuloAsignado(${index})">✖</button>
    `;
    contenedor.appendChild(div);
  });
}

function eliminarArticuloAsignado(index) {
  const eliminado = listaAgregarLocal.splice(index, 1)[0];
  const art = articulosActivosLocal.find(a => a.idArticulo === eliminado.idArticulo);
  if (art) art.seleccionado = false;
  actualizarListaAsignados();
  renderizarListaActivos();
}

const formNuevoProveedor = document.getElementById('formNuevoProveedor');
formNuevoProveedor.addEventListener('submit', async function(e) {
  e.preventDefault();

  if (listaAgregarLocal.length === 0) {
    alert("Debe asignar al menos un artículo.");
    return;
  }

  const proveedor = {
    nombreProveedor: formNuevoProveedor.nombreProveedor.value.trim(),
    direccion: formNuevoProveedor.direccion.value.trim(),
    mail: formNuevoProveedor.mail.value.trim(),
    telefono: formNuevoProveedor.telefono.value.trim()
  };

  const data = {
    proveedor,
    articulos: listaAgregarLocal
  };

  try {
    const res = await fetch('http://localhost:5000/Proveedor/crea-prov-art', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const msg = await res.json();
    if (res.ok) {
      alert(msg.mensaje || "Proveedor creado exitosamente");
      cerrarModalCrearProveedorCompleto();
      listaAgregarLocal = [];
      articulosActivosLocal = [];
      cargarProveedores();
    } else {
      alert("Error: " + (msg.error || msg));
    }
  } catch (err) {
    alert("Error de red: " + err.message);
  }
});

document.addEventListener('DOMContentLoaded', () => {
  cargarProveedores();
});
</script>
<?php endif; ?>