<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'generar';

include __DIR__ . '/../../tabs/ventas-tabs.php';
?>

<?php if ($action === 'generar'): ?>

<h2>Generar Venta</h2>
<div class="contenedor-ventas-generar">
  <div class="columna">
    <h4>Descripción de venta</h4>
    <textarea id="descripcionVenta" rows="8" class="textarea-base"></textarea>
  </div>

  <div class="columna">
    <h4>Artículos disponibles</h4>
    <ul id="listaArticulosDisponibles"></ul>
  </div>

  <div class="columna">
    <h4>Artículos seleccionados</h4>
    <ul id="articulosSeleccionados" class="articulo-item"></ul>
  </div>
</div>
<div style="text-align: center;">
  <button class="boton-accion" onclick="previsualizarVenta()">Registrar Venta</button>
</div>
<div id="modalResumenVenta" class="modal">
  <div class="modal-content">
  <h3 id="resumenDescripcion"></h3>
  <table class="tabla-base">
    <thead>
      <tr>
        <th>ID Artículo</th>
        <th>Nombre</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody id="resumenDetallesVenta"></tbody>
  </table>
  <div id="totalVentaModal" class="articulo-item">
    Total: $0.000
  </div>

  <div id="advertenciasVentaModal" class="mensaje-advertencias">

  </div>

  <div style="text-align: center;">
    <button class="boton-accion" onclick="cerrarResumenVenta()">Cerrar</button>
    <button class="boton-accion" onclick="confirmarVenta()">Confirmar</button>
  </div>
  </div>
</div>


<script>
let articulosDisponibles = [], articulosVenta = [];
let totalVentaConfirmar = 0;

async function cargarArticulosDisponibles() {
  try {
    const res = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
    const data = await res.json();
    articulosDisponibles = data;

    const ul = document.getElementById('listaArticulosDisponibles');
    ul.innerHTML = '';
    data.forEach(art => {
      const li = document.createElement('li');
      li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
      li.style.cursor = 'pointer';
      li.style.padding = '8px'
      li.style.marginBottom = '4px';
      li.style.borderBottom = '1px solid #333';
       li.style.borderRadius = '4px';
       li.style.transition = 'background 0.3s';
li.addEventListener('mouseover', () => {
    if (!li.dataset.selected) {
      li.style.backgroundColor = '#222';
    }
  });

  li.addEventListener('mouseout', () => {
    if (!li.dataset.selected) {
      li.style.backgroundColor = 'transparent';
    }
  });

  li.addEventListener('click', () => {

    document.querySelectorAll('#listaArticulosDisponibles li').forEach(el => {
      el.dataset.selected = '';
      el.style.backgroundColor = 'transparent';
    });

    li.dataset.selected = 'true';
    li.style.backgroundColor = '#008b8b';
    li.style.color = 'white';
});
      li.onclick = () => seleccionarArticulo(art);
      
      ul.appendChild(li);
    });
  } catch (err) {
    alert("❌ Error al cargar artículos: " + err.message);
  }
}

function seleccionarArticulo(articulo) {
  if (articulosVenta.find(a => a.idArticulo === articulo.idArticulo)) return;
  articulosVenta.push({ idArticulo: articulo.idArticulo,nombreArticulo: articulo.nombreArticulo, cantidadArticulo: 1 });
  renderizarArticulosSeleccionados();
}

function eliminarArticulo(index) {
  articulosVenta.splice(index, 1);
  renderizarArticulosSeleccionados();
}

function renderizarArticulosSeleccionados() {
  const ul = document.getElementById('articulosSeleccionados');
  ul.innerHTML = '';

  articulosVenta.forEach((a, i) => {
    const li = document.createElement('li');
    li.innerHTML = `
      <div>
        <strong>ID:</strong> ${a.idArticulo} - ${a.nombreArticulo}<br>
        <label class="label">Cantidad: </label>
        <input class="input" id="inputCantidad-${a.idArticulo}" type="number" value="${a.cantidadArticulo}" min="1" max="999999" step="1"
               onchange="actualizarCantidad(${i}, this.value)">
        <button onclick="eliminarArticulo(${i})" class="boton-accion">Quitar</button>
        <button onclick="verificarStockDesdeInput(${a.idArticulo})" class="boton-accion">Verificar stock</button>
      </div>
      <hr style="margin: 8px 0;">
    `;
    ul.appendChild(li);
  });
}

function verificarStockDesdeInput(idArticulo) {
  const input = document.getElementById(`inputCantidad-${idArticulo}`);
  const cantidad = parseInt(input.value);

  if (!cantidad || cantidad <= 0) {
    alert("⚠️ Cantidad inválida");
    return;
  }

  verificarStock(idArticulo, cantidad);
}


function actualizarCantidad(index, valor) {
  const cantidad = Number(valor);
  if (Number.isInteger(cantidad) && cantidad >= 1 && cantidad <= 999999) {
    articulosVenta[index].cantidadArticulo = cantidad;
  } else {
    alert("⚠️ Error: revisar cantidades ingresadas");
    renderizarArticulosSeleccionados();
  }
}

async function verificarStock(idArticulo, cantidad) {
  try {
    const res = await fetch(`http://localhost:5000/api/Ventas/validar-stock`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ idArticulo, cantidad })
    });

    const contentType = res.headers.get("content-type");
    const data = contentType && contentType.includes("application/json") ? await res.json() : await res.text();

    if (res.ok) {
      if (typeof data === 'boolean') {
        alert(data
          ? "✅ Stock suficiente disponible"
          : "⚠️ Stock insuficiente para este artículo");
      } else {
        alert("⚠️ Respuesta inesperada del servidor");
      }
    } else {
      const mensaje = data.mensaje || data.error || data || "❌ No se pudo validar el stock";
      alert("❌ Error: " + mensaje);
    }
  } catch (err) {
    alert("❌ Error de red al validar stock: " + err.message);
  }
}

async function previsualizarVenta() {
  const descripcion = document.getElementById('descripcionVenta').value.trim();
  if (!descripcion || articulosVenta.length === 0) {
    alert("⚠️ Completar descripción de venta y seleccionar al menos un artículo");
    return;
  }

  const dto = {
    descripcionVenta: descripcion,
    detalles: articulosVenta
  };

  try {
    const res = await fetch('http://localhost:5000/api/Ventas/visualizar-venta', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dto)
    });

    const data = await res.json();

    if (res.ok) {
      const venta = data.venta;
      const resultado = data.resultado;

      document.getElementById('resumenDescripcion').textContent = venta.descripcionVenta || descripcion;

      const tbody = document.getElementById('resumenDetallesVenta');
      tbody.innerHTML = '';

      venta.detalles.forEach(d => {
        const art = articulosDisponibles.find(a => a.idArticulo === d.idArticulo);
        const nombre = art ? art.nombreArticulo : '❓No encontrado';

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${d.idArticulo}</td>
          <td>${nombre}</td>
          <td>${d.cantidadArticulo}</td>
          <td style="text-align: right;">$${d.subtotalVenta.toFixed(3)}</td>
        `;
        tbody.appendChild(tr);
      });

      totalVentaConfirmar = venta.totalVenta ?? 0;
      document.getElementById('totalVentaModal').textContent = `Total: $${totalVentaConfirmar.toFixed(3)}`;

      articulosVenta = venta.detalles;

      const advContainer = document.getElementById('advertenciasVentaModal');
      if (resultado.advertencias && resultado.advertencias.length > 0) {
        advContainer.innerHTML = `
          <div style="background-color: #0a141dff; border: 1px solid #87bfff; padding: 1em; margin-top: 1em;">
            <h4 style="margin-top:0;">Avisos:</h4>
            <ul style="margin:0; padding-left: 1.2em;">
              ${resultado.advertencias.map(msg => `<li>${msg}</li>`).join('')}
            </ul>
          </div>
        `;
      } else {
        advContainer.innerHTML = '';
      }

      document.getElementById('modalResumenVenta').style.display = 'block';

    } else {
      const mensaje = data.mensaje || data.error || "❌ No se pudo previsualizar la venta";
      alert("❌ Error: " + mensaje);
    }
  } catch (err) {
    alert("❌ Error de red al previsualizar venta: " + err.message);
  }
}

function cerrarResumenVenta() {
  document.getElementById('modalResumenVenta').style.display = 'none';
}

async function confirmarVenta() {
  const dto = {
    descripcionVenta: document.getElementById('descripcionVenta').value.trim(),
    detalles: articulosVenta,
    totalVenta: totalVentaConfirmar
  };

  try {
    const res = await fetch('http://localhost:5000/api/Ventas/crear-venta', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dto)
    });

    const data = await res.json();

    if (res.ok) {
      let advertencias = data.advertencias || [];
      if (advertencias.length > 0) {
        alert("⚠️ Avisos:\n" + advertencias.join("\n"));
      }
      alert(data.mensaje || "✅ Venta registrada correctamente");

      cerrarResumenVenta();
      document.getElementById('descripcionVenta').value = '';
      articulosVenta = [];
      totalVentaConfirmar = 0;
      renderizarArticulosSeleccionados();
      document.getElementById('totalVentaModal').textContent = 'Total: $0.000';
    } else {
      const mensaje = data.mensaje || data.error || "❌ No se pudo confirmar la venta";
      alert("❌ Error: " + mensaje);
    }
  } catch (err) {
    alert("❌ Error de red al confirmar venta: " + err.message);
  }
}

document.addEventListener('DOMContentLoaded', cargarArticulosDisponibles);
</script>
<?php endif; ?>