    <?php
    use GuzzleHttp\Client;

    $action = $_GET['action'] ?? 'abm-art';

    include __DIR__ . '/../../tabs/maestro-articulos-tabs.php';
    ?>

    <?php if ($action === 'abm-art'): ?>

    <div id="modalCrearArticulo" class="modal">
        <div class="modal-content">
            <span class="close" 
                onclick="
                document.getElementById('modalCrearArticulo').style.display='none';
                document.getElementById('idArticulo').value = '';
                document.querySelector('#modalCrearArticulo h3').textContent = 'Crear nuevo artículo';
                const form = document.getElementById('formCrearArticulo');
                form.querySelector('input[type=submit]').value = 'Guardar artículo';
                form.reset();
                document.getElementById('modeloInv').dispatchEvent(new Event('change'));
                ">&times;</span>
            <h3>Crear nuevo artículo</h3>
            <form id="formCrearArticulo">
                <input type="hidden" name="idArticulo" id="idArticulo">
                <label>Nombre: <input type="text" name="nombreArticulo" required></label><br><br>
                <label>Descripción: <input type="text" name="descripcion" required></label><br><br>
                <label>Modelo de inventario:
                    <select name="modeloInv" id="modeloInv" required></select>
                </label><br><br>
                <div class="flex-row">
                    <label>Demanda estimada:
                        <input type="number" name="demandaEst" min="1" max="999999" required>
                    </label>
                    
                    <label>Unidad de tiempo:
                        <select name="unidadTemp" id="unidadTemp" required></select>
                    </label>
                </div>
                <br><br>
                <label>Costo anual de almacén ($):
                    <input type="number" name="costoAlmacen" step="0.01" min="0.01" max="999999" required>
                </label><br><br>
                <label>Período de revisión (en dias):
                    <input type="number" name="tiempoRevisionDias" id="tiempoRevisionDias" min="1" max="999999" disabled required>
                </label><br><br>
                <label>Stock máx.:
                    <input type="number" name="stockMax" id="stockMax" min="1" max="999999" required>
                </label><br><br>
                <label>Nivel de servicio esperado:
                    <input type="number" name="nivelServicio" step="0.0001" min="0.0001" max="100" required>
                </label><br><br>
                <label>Desviación estándar anual:
                    <input type="number" name="desviacionEstandarDemanda" step="0.0001" min="0.0001" max="999999" required>
                </label><br><br>
                    <input type="submit" value="Guardar artículo" class="boton-accion">
                </form>
            </div>
        </div>

        <div id="modalProveedores" class="modal">
            <div class="modal-content modal-proveedores">
                <span class="close" onclick="cerrarModalProveedores()">&times;</span>  
                <h3>Proveedores del Artículo</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Precio Unitario</th>
                                <th>Costo de pedido</th>
                                <th>Tiempo de entrega de pedido (días)</th>
                                <th>Prov. Predeterminado</th>
                            </tr>
                        </thead>
                        <tbody id="proveedoresBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <div id="modalAjusteStock" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="cerrarModalAjusteStock()" style="cursor:pointer;float:right;font-size:24px;">&times;</span>
            <h3 id="tituloAjusteStock">Ajuste de Stock</h3>

            <form id="formAjusteStock">
            <input type="hidden" id="ajusteIdArticulo">
            <label>
                Stock Actual:
                <input type="number" id="inputStockActual" min="0" required style="width:100%; padding:4px;">
            </label><br><br>

            <label>
                Stock Máximo:
                <input type="number" id="inputStockMax" min="1" required style="width:100%; padding:4px;">
            </label><br><br>

            <div style="text-align:right;">
                <button type="button" onclick="cerrarModalAjusteStock()" style="margin-right:8px;">Cancelar</button>
                <button type="submit">Guardar</button>
            </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/katex.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const elems = document.querySelectorAll('.katex-fraccion');
        elems.forEach(el => {
            const latex = el.getAttribute('data-latex');
            if (window.katex && latex) {
                katex.render(latex, el, {
                    throwOnError: false,
                    displayMode: false
                });
            }
        });
    });
    </script>

    <script>
    window.addEventListener('DOMContentLoaded', async () => {
        const modeloSelect = document.getElementById('modeloInv');
        const uTempSelect = document.getElementById('unidadTemp');
        const tiempoInput = document.getElementById('tiempoRevisionDias');

        try {
            const modelos = await fetch('http://localhost:5000/MaestroArticulos/modeloInventario/lista-modelos').then(rs => rs.json());
            modelos.forEach(modInv => {
                const opt = document.createElement('option');
                opt.value = modInv.id;
                opt.textContent = modInv.nombreModInv;
                modeloSelect.appendChild(opt);
            });

            const unidadesTemp = await fetch('http://localhost:5000/MaestroArticulos/modeloInventario/lista-unidades-temp').then(rs => rs.json());
            unidadesTemp.forEach(uTemp => {
                const opt = document.createElement('option');
                opt.value = uTemp.id;
                opt.textContent = uTemp.nombreUnidadTemp;
                uTempSelect.appendChild(opt);
            });

            modeloSelect.addEventListener('change', () => {
                const selectedId = parseInt(modeloSelect.value);
                tiempoInput.disabled = (selectedId !== 2); 
            });

            uTempSelect.addEventListener('change', () => {
                const selectedId = parseInt(uTempSelect.value);
            });

        } catch (err) {
            alert("error cargando modelos de inventario: " + err);
        }
    });

    document.getElementById('formCrearArticulo').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const modeloId = parseInt(form.modeloInv.value);
        const unidadTempId = parseInt(form.unidadTemp.value);
        const idArticulo = document.getElementById('idArticulo').value;
        const demandaEstRaw = form.demandaEst.value;
        const stockMaxRaw = form.stockMax.value;
        const costoAlmacenRaw = form.costoAlmacen.value;
        const tiempoRevisionRaw = form.tiempoRevisionDias.value;
        const nivelServicioRaw = form.nivelServicio.value;
        const desviacionRaw = form.desviacionEstandarDemanda.value;

        const data = {
            nombreArticulo: form.nombreArticulo.value.trim(),
            descripcion: form.descripcion.value.trim(),
            modeloInv: modeloId,
            demandaEst: Number(demandaEstRaw),
            unidadTemp: unidadTempId,
            stockMax: Number(stockMaxRaw),
            costoAlmacen: Number(costoAlmacenRaw),
            tiempoRevisionDias: modeloId === 2 ? Number(tiempoRevisionRaw) : 0,
            nivelServicio: Number(nivelServicioRaw),
            desviacionEstandarDemanda: Number(desviacionRaw),
            idMaster: 1
        };

        if (
            !Number.isInteger(data.demandaEst) || data.demandaEst < 1 || data.demandaEst > 999999 ||
            !Number.isInteger(data.stockMax) || data.stockMax < 1 || data.stockMax > 999999 ||
            !Number.isFinite(data.costoAlmacen) || data.costoAlmacen <= 0.01 || data.costoAlmacen > 999999 ||
            !Number.isFinite(data.nivelServicio) || data.nivelServicio < 0.0001 || data.nivelServicio > 100 ||
            !Number.isFinite(data.desviacionEstandarDemanda) || data.desviacionEstandarDemanda < 0.0001 || data.desviacionEstandarDemanda > 999999 ||
            (modeloId === 2 && (!Number.isInteger(data.tiempoRevisionDias) || data.tiempoRevisionDias < 1 || data.tiempoRevisionDias > 999999))
        ) {
            alert("Verificar valores numéricos ingresados.");
            return;
        }

        if (idArticulo) {
            data.idArticulo = parseInt(idArticulo);
            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulo/UpdateArticulo', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                if (resp.ok) {
                    alert("Artículo modificado con éxito ");
                    location.reload();
                } else {
                    const msg = await resp.text();
                    alert("Error al modificar el artículo: " + msg);
                }
            } catch (err) {
                alert("Error de red: " + err);
            }
        } else {
            try {
                const resp = await fetch('http://localhost:5000/MaestroArticulos/articulo/CreateArticulo', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                if (resp.ok) {
                    alert("Artículo creado con éxito ");
                    location.reload();
                } else {
                    const msg = await resp.text();
                    alert("Error al crear el artículo: " + msg);
                }
            } catch (err) {
                alert("Error de red o conexión: " + err);
            }
        }
    });

    function editarArticulo(articulo) {
        document.getElementById('modalCrearArticulo').style.display = 'block';

        const form = document.getElementById('formCrearArticulo');
        form.nombreArticulo.value = articulo.nombreArticulo || '';
        form.descripcion.value = articulo.descripcion || '';
        form.demandaEst.value = articulo.demandaEst || '';
        form.costoAlmacen.value = articulo.costoAlmacen || '';
        form.tiempoRevisionDias.value = articulo.tiempoRevisionDias || '';
        form.stockMax.value = articulo.stockMax || '';
        document.getElementById('idArticulo').value = articulo.idArticulo;
        document.getElementById('modeloInv').value = articulo.modeloInvId || '';
        document.getElementById('unidadTemp').value = articulo.unidadTempId || '';
        document.getElementById('tiempoRevisionDias').disabled = (parseInt(articulo.modeloInvId) !== 2);
        document.querySelector('#modalCrearArticulo h3').textContent = 'Editar Artículo';
        form.querySelector('input[type="submit"]').value = 'Guardar Cambios';
        form.setAttribute('data-modo', 'editar');
    }

    async function eliminarArticulo(idArticulo) {
        if (!confirm('¿Confirma que desea eliminar este artículo? Esta acción no se puede deshacer ')) {
            return;
        }

        try {
            const resp = await fetch(`http://localhost:5000/MaestroArticulos/articulo/DeleteArticulo/${idArticulo}`, {
                method: 'DELETE'
            });

            if (resp.ok) {
                alert('Artículo eliminado con éxito ');
                location.reload();
            } else {
                const msg = await resp.text();
                alert('Error al eliminar el artículo: ' + msg);
            }
        } catch (err) {
            alert('Error de red o conexión: ' + err);
        }
    }

    async function verProveedores(idArticulo) {
        const modal = document.getElementById('modalProveedores');
        const tbody = document.getElementById('proveedoresBody');

        tbody.innerHTML = '';

        try {
            const response = await fetch(`http://localhost:5000/MaestroArticulos/articulosLista/proveedores/${idArticulo}`);
            if (!response.ok) throw new Error('Error al obtener proveedores');

            const proveedores = await response.json();

            if (proveedores.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No hay proveedores asociados</td></tr>';
            } else {
                proveedores.forEach(p => {
                const tr = document.createElement('tr');

                function crearCelda(text, isCenter = false) {
                    const td = document.createElement('td');
                    td.textContent = text;
                    if (isCenter) {
                    }
                    return td;
                }

                tr.appendChild(crearCelda(p.nombreProveedor || ''));
                tr.appendChild(crearCelda(p.emailProveedor || ''));
                tr.appendChild(crearCelda(p.telProveedor || ''));
                tr.appendChild(crearCelda(p.direccionProveedor || ''));
                tr.appendChild(crearCelda(p.precioUnitario != null ? p.precioUnitario.toFixed(3) : ''));
                tr.appendChild(crearCelda(p.costoPedido != null ? p.costoPedido.toFixed(3) : ''));
                tr.appendChild(crearCelda(p.tiempoEntregaDias || ''));
                tr.appendChild(crearCelda(p.predeterminado ? 'Sí' : 'No', true));

                tbody.appendChild(tr);
                });
            }
            modal.style.display = 'block';
        } catch (err) {
            alert('Error cargando proveedores: ' + err.message);
        }
    }

    function cerrarModalProveedores() {
        document.getElementById('modalProveedores').style.display = 'none';
    }

    async function calcularParametros() {
    if (!confirm("¿Confirma que desea recalcular parámetros?")) return;

    try {
        const resp = await fetch('http://localhost:5000/MaestroArticulos/modeloInventario/calc-mod-inv');

        if (!resp.ok) {
        const msg = await resp.text();
        alert("Error en el cálculo: " + msg);
        return;
        }

        const contentType = resp.headers.get('content-type');

        if (contentType && contentType.includes('application/json')) {
        const datos = await resp.json();

        if (Array.isArray(datos) && datos.length === 0) {
            alert("No se encontraron artículos para calcular, o ninguno de los existentes posee proveedor predeterminado");
        } else {

            const sinProveedor = datos.filter(d => !d.proveedor || d.proveedor.trim() === '');

            if (sinProveedor.length > 0) {
            const lista = sinProveedor.map(d => `• #${d.idArticulo} - ${d.nombreArticulo}`).join('\n');
            alert("⚠️ Los siguientes artículos no cuentan con proveedor predeterminado:\n\n" + lista);
            }

            alert("✅ Parámetros de inventario calculados exitosamente.");
        }

        } else {
        const texto = await resp.text();
        alert("Resultado: " + texto);
        }

        location.reload();

    } catch (err) {
        alert("❌ Error de red: " + err.message);
    }
    }

    function abrirModalCrearArticulo() {
        const modal = document.getElementById('modalCrearArticulo');
        const form = document.getElementById('formCrearArticulo');

        modal.style.display = 'block';

        document.getElementById('idArticulo').value = '';
        document.querySelector('#modalCrearArticulo h3').textContent = 'Crear nuevo artículo';
        form.querySelector('input[type=submit]').value = 'Guardar artículo';
        form.reset();

        const modeloSelect = document.getElementById('modeloInv');
        modeloSelect.selectedIndex = 0; 
        modeloSelect.dispatchEvent(new Event('change')); 
    }

    async function ajusteStock(idArticulo) {
        try {
            const resp = await fetch(`http://localhost:5000/MaestroArticulos/articulo-datos/${idArticulo}`);
            if (!resp.ok) {
                const text = await resp.text();
                alert("Error al obtener datos del artículo: " + text);
                return;
            }
            const art = await resp.json();

            document.getElementById('ajusteIdArticulo').value = art.idArticulo;
            document.getElementById('inputStockActual').value = art.stockActual ?? 0;
            document.getElementById('inputStockMax').value = art.stockMax ?? 1;
            document.getElementById('tituloAjusteStock').textContent = `ID ${art.idArticulo} - ${art.nombreArticulo}`;
            document.getElementById('modalAjusteStock').style.display = 'block';

        } catch (err) {
            alert("Error de red al obtener artículo: " + err.message);
        }
    }

    function cerrarModalAjusteStock() {
        document.getElementById('modalAjusteStock').style.display = 'none';
    }

    document.getElementById('formAjusteStock').addEventListener('submit', async function(ev) {
        ev.preventDefault();

        const idArticulo = parseInt(document.getElementById('ajusteIdArticulo').value);
        const stockActual = Number(document.getElementById('inputStockActual').value);
        const stockMax = Number(document.getElementById('inputStockMax').value);

        if (
            !Number.isInteger(stockMax) || stockMax < 1 || stockMax > 999999 ||
            !Number.isInteger(stockActual) || stockActual < 0 || stockActual > stockMax
        ) {
            alert("Verificar valores numéricos ingresados. ");
            return;
        }

        const dto = {
        idArticulo: idArticulo,
        nombreArticulo: "", 
        descripcion: "",
        modeloInv: "",
        proveedor: "",
        demandaEst: 0,
        unidadTemp: "",  
        costoAlmacen: 0,
        tiempoRevisionDias: 0,
        stockActual: stockActual,
        stockSeguridad: 0,
        puntoPedido: 0,
        stockMax: stockMax,
        cgi: 0,
        };

        try {
            const resp = await fetch('http://localhost:5000/MaestroArticulos/ajuste-stock', {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dto)
            });

            if (resp.ok) {
                alert("Inventario actualizado correctamente.");
                cerrarModalAjusteStock();
                location.reload();
            } else {
                const json = await resp.json();
                alert("Error al actualizar inventario: " + (json.error || JSON.stringify(json)));
            }

        } catch (err) {
            alert("Error de red al actualizar inventario: " + err.message);
        }
    });

    </script>

        <?php
        $client = new Client(['base_uri' => 'http://localhost:5000']); 

        try {
            $response = $client->get('/MaestroArticulos/articulos/list-art-datos'); 
            $articulos = json_decode($response->getBody(), true);
        } catch (Exception $e) {
            $articulos = [];
            echo "<p style='color:red;'>Error al obtener artículos: " . $e->getMessage() . "</p>";
        }
        ?>

    <div class="table-header">
        <h2>Lista de Artículos Activos</h2>
        <div class="acciones">
            <button class="boton-accion" onclick="abrirModalCrearArticulo()">Añadir artículo</button>
            <button class="boton-accion" onclick="calcularParametros()">Calcular parámetros de stock</button>
        </div>
    </div>

    <div class="tabla-contenedor">
      <div class="table-scroll-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Prov. predeterminado</th>
                    <th>Modelo Inv.</th>
                    <th>Demanda estimada</th>
                    <th>Costo anual de almacén ($)</th>
                    <th>Tiempo de revisión (días)</th>
                    <th>Stock Actual</th>
                    <th>Stock Máx.</th>
                    <th>Stock de Seguridad</th>
                    <th>Punto de Pedido</th>
                    <th>CGI</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articulos)): ?>
                    <tr><td colspan="14" style="text-align:center;">No hay artículos activos.</td></tr>
                <?php else: ?>
                    <?php 
                    $modalesAdvertencia = ""; // acumulador HTML
                    foreach ($articulos as $art): 
                        $advertencias = [];

                        $modelo = strtolower($art['modeloInv'] ?? '');

                        if ($modelo === 'lotefijo_q') {
                            if (($art['puntoPedido'] ?? 0) >= ($art['stockMax'] ?? 0)) {
                                $advertencias[] = "El punto de pedido no puede ser igual o mayor que el stock máximo.";
                            }
                            if (($art['stockActual'] ?? 0) <= ($art['puntoPedido'] ?? 0)) {
                                $advertencias[] = "El stock actual está por debajo o igual al punto de pedido.";
                            }
                            if (($art['stockActual'] ?? 0) <= ($art['stockSeguridad'] ?? 0)) {
                                $advertencias[] = "El stock actual está por debajo o igual al stock de seguridad.";
                            }
                        } elseif ($modelo === 'periodofijo_p') {
                            if (($art['stockActual'] ?? 0) <= ($art['stockSeguridad'] ?? 0)) {
                                $advertencias[] = "El stock actual está por debajo o igual al stock de seguridad.";
                            }
                        }

                        $hayAdvertencias = !empty($advertencias);
                        $idModalAdvertencia = "modalAdvertencia_" . $art['idArticulo'];
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($art['idArticulo']) ?>
                            <?php if ($hayAdvertencias): ?>
                                <button 
                                    title="Advertencia"
                                    class="boton-advertencia"
                                    onclick="document.getElementById('<?= $idModalAdvertencia ?>').style.display = 'block';"
                                >⚠️</button>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($art['nombreArticulo']) ?></td>
                        <td><?= htmlspecialchars($art['descripcion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['proveedor'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['modeloInv'] ?? '') ?></td>
                        <?php
                            $unidadTemp = strtolower($art['unidadTemp'] ?? '');
                            $numerador = 'un.';
                            $denominador = match ($unidadTemp) {
                                'semanal' => 'semana',
                                'mensual' => 'mes',
                                'anual' => 'año',
                                default => '',
                            };
                            $fraccionLatex = "\\frac{{$numerador}}{{$denominador}}";
                        ?>
                        <td style="text-align:center;">
                            <?= htmlspecialchars($art['demandaEst']) ?>
                            <span class="katex-fraccion" data-latex="<?= htmlspecialchars($fraccionLatex) ?>"></span>
                        </td>
                        <td><?= htmlspecialchars($art['costoAlmacen'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['tiempoRevisionDias'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['stockActual'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['stockMax'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['stockSeguridad'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['puntoPedido'] ?? '') ?></td>
                        <td><?= htmlspecialchars($art['cgi'] ?? '') ?></td>
                        <td class="acciones-col">
                            <?php $jsonArt = base64_encode(json_encode($art)); ?>
                            <button onclick="editarArticulo(JSON.parse(atob('<?= $jsonArt ?>')))" title="Editar" class="boton-icono">✏️</button>
                            <button onclick="eliminarArticulo(<?= $art['idArticulo'] ?>)" title="Eliminar" class="boton-icono">❌</button>
                            <button onclick="verProveedores(<?= $art['idArticulo'] ?>)" title="Ver Proveedores" class="boton-icono">👤</button>
                            <button onclick="ajusteStock(<?= $art['idArticulo'] ?>)" title="Ajustar stock" class="boton-icono">🔧</button>
                        </td>
                    </tr>
                    <?php if ($hayAdvertencias):
                        ob_start(); ?>
                        <div class="modal" id="<?= $idModalAdvertencia ?>" style="display:none;">
                            <div class="modal-content advertencia">
                                <span class="close" onclick="document.getElementById('<?= $idModalAdvertencia ?>').style.display='none'">&times;</span>
                                <h3>Advertencias para artículo: <?= htmlspecialchars($art['nombreArticulo']) ?></h3>
                                <ul>
                                    <?php foreach ($advertencias as $msg): ?>
                                        <li style="color:red;"><?= htmlspecialchars($msg) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php 
                        $modalesAdvertencia .= ob_get_clean(); 
                    endif;
                    endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
      </div>
    </div>

    <?= $modalesAdvertencia ?>

    <?php endif; ?>