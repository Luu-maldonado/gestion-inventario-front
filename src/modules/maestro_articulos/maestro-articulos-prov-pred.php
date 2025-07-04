    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'prov-pred';

    include __DIR__ . '/../../tabs/maestro-articulos-tabs.php';
    ?>

    <?php if ($action === 'prov-pred'): ?>
    <div class="prov-pred-container">

        <div class="lista-articulos">
            <h4>Artículos</h4>
            <ul id="listaArticulos"></ul>
        </div>

        <div class="info-proveedores">
            <h4>Proveedores</h4>
            <div id="infoProveedores">
                <p>Seleccioná un artículo para ver sus proveedores.</p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        const listaArticulos = document.getElementById('listaArticulos');
        const infoProveedores = document.getElementById('infoProveedores');

        try {
            const resp = await fetch('http://localhost:5000/MaestroArticulos/articulos/list-art-datos');
            const articulos = await resp.json();

            articulos.forEach(art => {
                const li = document.createElement('li');
                li.textContent = `#${art.idArticulo} - ${art.nombreArticulo}`;
                li.style.cursor = 'pointer';
                li.style.margin = '0.5em 0';
                li.onclick = () => {
                    document.querySelectorAll('#listaArticulos li').forEach(item => item.classList.remove('selected'));
                    li.classList.add('selected');
                    cargarProveedores(art.idArticulo);
                };

        listaArticulos.appendChild(li);
    });

        } catch (err) {
            listaArticulos.innerHTML = "<li>Error al cargar artículos </li>";
        }

        async function cargarProveedores(idArticulo) {
            infoProveedores.innerHTML = '<p>Cargando proveedores...</p>';

            try {
                const resp = await fetch(`http://localhost:5000/MaestroArticulos/articulosLista/proveedores/${idArticulo}`);
                const proveedores = await resp.json();

                if (!proveedores.length) {
                    infoProveedores.innerHTML = '<p>No hay proveedores asociados </p>';
                    return;
                }

                const table = document.createElement('table');
                table.classList.add('tabla-base');
                table.border = "1";
                table.cellPadding = "5";
                table.cellSpacing = "0";
                table.style.width = "100%";
                table.style.borderCollapse = "collapse";

                const thead = document.createElement('thead');
                thead.innerHTML = `
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acción</th>
                    </tr>`;
                table.appendChild(thead);

                const tbody = document.createElement('tbody');

                proveedores.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${p.idProveedor}</td>
                        <td>${p.nombreProveedor}</td>
                        <td>
                            ${p.predeterminado
                                ? '<span style="color: green;">(predeterminado)</span>'
                                : `<button onclick="establecerPredeterminado(${idArticulo}, ${p.idProveedor})">Cambiar a predeterminado</button>`
                            }
                        </td>`;
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                infoProveedores.innerHTML = '';
                infoProveedores.appendChild(table);
            } catch (err) {
                infoProveedores.innerHTML = '<p>Error al cargar proveedores </p>';
            }
        }

        window.establecerPredeterminado = async function(idArticulo, idProveedor) {
            if (!confirm("¿Deseás establecer este proveedor como predeterminado del articulo?")) return;

            try {
                const resp = await fetch(`http://localhost:5000/MaestroArticulos/proveedor/predeterminado?idArticulo=${idArticulo}&idProveedor=${idProveedor}`, {
                    method: 'POST'
                });

                const msg = await resp.text();
                alert(msg);
                // recargar proveedores actualizados
                cargarProveedores(idArticulo);
            } catch (err) {
                alert("Error al cambiar proveedor predeterminado ");
            }
        };
    });
    </script>
    <?php endif; ?>