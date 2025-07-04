    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'lista-art-faltantes';

    include __DIR__ . '/../../tabs/maestro-articulos-tabs.php';
    ?>

    <?php if ($action === 'lista-art-faltantes'): ?>
            <h2>Lista de Articulos Faltantes</h2>
        <div id="listaFaltantesContainer" style="margin-top: 1em;">
            <p>Cargando lista...</p>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', async () => {
            const container = document.getElementById('listaFaltantesContainer');

            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulosLista/faltantes');
                if (!resp.ok) throw new Error('Error al obtener lista de articulos faltantes');

                const articulos = await resp.json();

                if (articulos.length === 0) {
                    container.innerHTML = '<p>No hay articulos faltantes </p>';
                    return;
                }

                const table = document.createElement('table');
                table.classList.add('tabla-base');
                table.style.margin = '0 auto';

                const thead = document.createElement('thead');
                thead.innerHTML = `
                    <tr">
                        <th>ID Artículo</th>
                        <th>Nombre</th>
                        <th>Stock Actual</th>
                        <th>Stock de Seguridad</th>
                    </tr>`;
                table.appendChild(thead);

                const tbody = document.createElement('tbody');

                articulos.forEach(art => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${art.idArticulo ?? art.IdArticulo ?? 0}</td>
                    <td>${art.nombreArticulo ?? art.NombreArticulo ?? ''}</td>
                    <td>${art.stockActual ?? art.StockActual ?? 0}</td>
                    <td>${art.stockSeguridad ?? art.StockSeguridad ?? 0}</td>
                    `;
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                container.innerHTML = '';
                container.appendChild(table);

            } catch (err) {
                container.innerHTML = `<p style="color:red;">Error cargando articulos faltantes: ${err.message}</p>`;
            }
        });
        </script>
    <?php endif; ?>