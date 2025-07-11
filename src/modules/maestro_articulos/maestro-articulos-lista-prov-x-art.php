    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'lista-prov-x-art';

    include __DIR__ . '/../../tabs/maestro-articulos-tabs.php';
    ?>

    <?php if ($action === 'lista-prov-x-art'): ?>
        <h2>Lista de Proveedores por Artículo</h2>

        <div class="lista-proveedores-container">
            <div class="lista-articulos">
                <h4>Artículos</h4>
                <ul id="listaArticulos"></ul>
            </div>

            <div class="info-proveedores">
                <h4>Proveedores</h4>
                <div id="infoProveedores">
                    <p>Seleccionar un artículo para ver sus proveedores</p>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const listaArticulos = document.getElementById('listaArticulos');
            const infoProveedores = document.getElementById('infoProveedores');

            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
                if (!resp.ok) throw new Error('Error al obtener lista de artículos');
                const articulos = await resp.json();

                articulos.forEach(art => {
                    const li = document.createElement('li');
                    li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
                    li.style.cursor = 'pointer';
                    li.style.margin = '0.5em 0';
                    li.onclick = () => cargarProveedores(art.idArticulo, li);
                    listaArticulos.appendChild(li);
                });
            } catch (err) {
                listaArticulos.innerHTML = '<li>Error al cargar artículos</li>';
            }

            let articuloSeleccionado = null;

            async function cargarProveedores(idArticulo, elementoLi) {
                
                if (articuloSeleccionado) articuloSeleccionado.style.backgroundColor = '';
                elementoLi.style.backgroundColor =" #006658"; 
                articuloSeleccionado = elementoLi;

                infoProveedores.innerHTML = '<p>Cargando proveedores...</p>';

                try {
                    const resp = await fetch(`http://localhost:5000/MaestroArticulos/articulosLista/proveedores/${idArticulo}`);
                    if (!resp.ok) throw new Error('Error al obtener proveedores');
                    const proveedores = await resp.json();

                    if (proveedores.length === 0) {
                        infoProveedores.innerHTML = '<p>No hay proveedores asociados.</p>';
                        return;
                    }

                    const table = document.createElement('table');
                    table.classList.add('tabla-base');

                    const thead = document.createElement('thead');
                    thead.innerHTML = `
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Precio Unitario</th>
                            <th>Costo Pedido</th>
                            <th>Tiempo Entrega (días)</th>
                            <th>Predeterminado</th>
                        </tr>`;
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');

                    proveedores.forEach(p => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${p.nombreProveedor || ''}</td>
                            <td>${p.emailProveedor || ''}</td>
                            <td>${p.telProveedor || ''}</td>
                            <td>${p.direccionProveedor || ''}</td>
                            <td>${p.precioUnitario != null ? p.precioUnitario.toFixed(3) : ''}</td>
                            <td>${p.costoPedido != null ? p.costoPedido.toFixed(3) : ''}</td>
                            <td>${p.tiempoEntregaDias || ''}</td>
                            <td>${p.predeterminado ? 'Sí' : 'No'}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    table.appendChild(tbody);
                    infoProveedores.innerHTML = '';
                    infoProveedores.appendChild(table);

                } catch (err) {
                    infoProveedores.innerHTML = `<p style="color:red;">Error al cargar proveedores: ${err.message}</p>`;
                }
            }
        });
        </script>
    <?php endif; ?>