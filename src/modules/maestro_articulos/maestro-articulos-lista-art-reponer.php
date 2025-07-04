    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'lista-art-reponer';

    include __DIR__ . '/../../tabs/maestro-articulos-tabs.php';
    ?>

    <?php if ($action === 'lista-art-reponer'): ?>
        <h2>Lista de Articulos a Reponer</h2>
        <div id="listaReponerContainer" style="margin-top: 1em;">
            <p>Cargando lista...</p>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const container = document.getElementById('listaReponerContainer');

            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulosLista/reponer');
                if (!resp.ok) throw new Error('Error al obtener lista de articulos a reponer');

                const articulos = await resp.json();

                if (articulos.length === 0) {
                    container.innerHTML = '<p>No hay articulos que requieran reposición de stock</p>';
                    return;
                }

                const table = document.createElement('table');
                table.classList.add('tabla-base');
                table.style.margin = '0 auto';

                const thead = document.createElement('thead');
                thead.innerHTML = `
                    <tr>
                        <th>ID Artículo</th>
                        <th>Nombre</th>
                        <th>Stock Actual</th>
                        <th>Punto de Pedido</th>
                    </tr>`;
                table.appendChild(thead);

                const tbody = document.createElement('tbody');

                articulos.forEach(art => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${art.idArticulo ?? art.IdArticulo ?? 0}</td>
                    <td>${art.nombreArticulo ?? art.NombreArticulo ?? ''}</td>
                    <td>${art.stockActual ?? art.StockActual ?? 0}</td>
                    <td>${art.puntoPedido ?? art.PuntoPedido ?? 0}</td>
                    `;
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);

                container.innerHTML = '';
                container.appendChild(table);

            } catch (err) {
                container.innerHTML = `<p style="color:red;">Error cargando articulos: ${err.message}</p>`;
            }
        });
        </script>
    <?php endif; ?>