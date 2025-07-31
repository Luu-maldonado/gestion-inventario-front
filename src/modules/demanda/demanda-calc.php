<?php 
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'calc';
include __DIR__ . '/../../tabs/demanda-tabs.php';

$client = new Client(['base_uri' => 'http://localhost:5000']);
$articulos = [];
$error = null;
$resultado = null;

$tipoPrediccion = $_POST['tipoPrediccion'] ?? null;
$tiposPrediccionNombres = [
    1=>'Promedio móvil',
    2=>'Promedio móvil ponderado',
    3=>'Suavización exponencial',
    4=>'Regresión lineal'
];
$tipoPrediccionNombre = $tipoPrediccion && isset($tiposPrediccionNombres[$tipoPrediccion]) ? $tiposPrediccionNombres[$tipoPrediccion] : null;
$periodo = $_POST['periodo'] ?? null;
$alfa = $_POST['alfa'] ?? null;
$idArticulo = $_POST['idArticulo'] ?? null;

try {
    $response = $client->get('/MaestroArticulos/articulos/list-art-datos');
    $articulos = json_decode($response->getBody()->getContents(), true);
} catch (Exception $err) {
    $error = $err->getMessage();
}

$nombreArticuloSeleccionado = null;
if ($idArticulo && !empty($articulos)) {
    foreach ($articulos as $art) {
        if ($art['idArticulo'] == $idArticulo) {
            $nombreArticuloSeleccionado = $art['nombreArticulo'];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idArticulo && $tipoPrediccion && $periodo) {
    $query = [
        'idArticulo' => $idArticulo,
        'tipoPrediccion' => $tipoPrediccion,
        'periodo' => $periodo
    ];
    if ($tipoPrediccion == 3 && $alfa !== null) {
        $query['alfa'] = $alfa;
        $alfaMostrar = (float)$alfa;
    }

    try {
        $res = $client->get('/api/Demanda/calcular-demanda', ['query' => $query]);
        $resultado = json_decode($res->getBody()->getContents(), true);
    } catch (GuzzleHttp\Exception\ClientException $err) {
        $responseBody = $err->getResponse()->getBody()->getContents();
        $decoded = json_decode($responseBody, true);
        $error = $decoded['mensaje'] ?? 'Error desconocido';
    }
}
?>

<?php if ($action === 'calc'): ?>
<main style="display: flex; gap: 8px; padding: 8px;">
  <div style="width: 20%; border-right: 1px solid #444; overflow-y: auto;">
    <h4>Artículos activos</h4>
    <?php if (!empty($error)): ?>
        <div id="errorNotificacion" class="error-box">
            ⚠️ <?= htmlspecialchars($error) ?>
            <span onclick="document.getElementById('errorNotificacion').style.display='none'" style="cursor:pointer; float:right;">&times;</span>
            <div style="margin-top: 1em; text-align: right;">
                <a href="http://localhost:8008/index.php?mod=demanda&action=calc" class="boton-recargar">Recargar lista</a>
            </div>
        </div>
    <?php else: ?>
      <form method="POST">
        <ul>
          <?php foreach ($articulos as $art): ?>
            <li>
              <label>
                <input type="radio" name="idArticulo" value="<?= $art['idArticulo'] ?>">
                <?= "[{$art['idArticulo']}] " . htmlspecialchars($art['nombreArticulo']) ?>
              </label>
            </li>
          <?php endforeach; ?>
        </ul>
        <h4>Seleccionar metodología</h4>
        <select name="tipoPrediccion" required>
          <option value="1">Promedio móvil</option>
          <option value="2">Promedio móvil ponderado</option>
          <option value="3">Suavización exponencial</option>
          <option value="4">Regresión lineal</option>
        </select>

        <h4>Seleccionar periodo de cálculo</h4>
        <select name="periodo" required>
          <option value="1">3 meses</option>
          <option value="2">6 meses</option>
          <option value="3">12 meses</option>
        </select>

        <div id="alfaInput" style="margin-top: 1em; display: none;">
          <label>Coeficiente de suavización (0-1):
            <input type="number" name="alfa" min="0" max="1" step="0.01">
          </label>
        </div>
        <p>&nbsp;</p>
        <button id="btnCalcularDemanda" type="submit" class="boton-recargar" disabled>Calcular</button>
      </form>
    <?php endif; ?>
  </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <div style="flex-grow: 1; padding-left: 8px;width: 80%;">
    <?php if ($resultado): ?>
      <h3>
        • Resultados <?= $tipoPrediccionNombre ? "({$tipoPrediccionNombre})" : '' ?>
        <?= $nombreArticuloSeleccionado ? " — Artículo: [{$idArticulo}] <em>{$nombreArticuloSeleccionado}</em>" : '' ?> •
      </h3>
      <p><strong>Demanda estimada = </strong> <?= $resultado['demanda'] ?></p>
      <p><strong>Desviación estandar = </strong> <?= $resultado['desviacionEstandarPeriodo'] ?></p>
      <?php if (isset($alfaMostrar)): ?>
        <p><strong>α = </strong> <?= $alfaMostrar ?></p>
      <?php endif; ?>

      <?php if (!empty($resultado['valoresTabla'])): ?>
        <table class="tabla-base">
          <thead><tr><th>Mes</th><th>Cantidad (ventas)</th></tr></thead>
          <tbody>
          <?php foreach ($resultado['valoresTabla'] as $i => $val): ?>
            <tr class="<?= $i === count($resultado['valoresTabla']) - 1 ? 'fila-final-demanda' : '' ?>">
              <td><?= $val['mes'] ?></td>
              <td><?= $val['cantidad'] ?></td>
            </tr>
            <?php if ($i === count($resultado['valoresTabla']) - 1): ?>
              <tr class="leyenda-demanda">
                <td colspan="2"> Demanda calculada para el próximo periodo</td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
          </tbody>
        </table>
      
        <canvas id="graficoDemanda" width="70%" height="200"></canvas>
        <script>
          const ctx = document.getElementById('graficoDemanda').getContext('2d');
          new Chart(ctx, {
            type: 'line',
            data: {
              labels: <?= json_encode(array_column($resultado['valoresTabla'], 'mes')) ?>,
              datasets: [{
                label: 'Demanda mensual',
                data: <?= json_encode(array_column($resultado['valoresTabla'], 'cantidad')) ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false,
                tension: 0.1
              }]
            },
            options: {
              responsive: true,
              plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Proyección de la demanda' }
              },
              scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
          });
        </script>

      <?php elseif (!empty($resultado['puntosXY'])): ?>
        <table class="tabla-base">
          <thead><tr><th>X</th><th>Real</th><th>Estimado</th></tr></thead>
          <tbody>
		<?php foreach ($resultado['puntosXY'] as $j => $p): ?>
		  <tr class="<?= $j === count($resultado['puntosXY']) - 1 ? 'fila-final-demanda' : '' ?>">
		    <td><?= $p['x'] ?></td>
		    <td><?= $p['yReal'] ?? '-' ?></td>
		    <td><?= $p['yEstimado'] ?></td>
		  </tr>
		  <?php if ($j === count($resultado['puntosXY']) - 1): ?>
		    <tr class="leyenda-demanda">
		      <td colspan="3">Demanda calculada para el próximo periodo</td>
		    </tr>
		  <?php endif; ?>
		<?php endforeach; ?>
          </tbody>
        </table>
        <canvas id="graficoXY" width="200" height="100"></canvas>
        <script>
          const ctxXY = document.getElementById('graficoXY').getContext('2d');
          const puntosXY = <?= json_encode($resultado['puntosXY']) ?>;
          new Chart(ctxXY, {
            type: 'line',
            data: {
              labels: puntosXY.map(p => p.x),
              datasets: [
                {
                  label: 'Y Real',
                  data: puntosXY.map(p => p.yReal),
                  borderColor: 'rgba(255, 99, 132, 1)',
                  tension: 0.1
                },
                {
                  label: 'Y Estimado',
                  data: puntosXY.map(p => p.yEstimado),
                  borderColor: 'rgba(54, 162, 235, 1)',
                  tension: 0.1
                }
              ]
            },
            options: {
              responsive: true,
              plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Regresión lineal' }
              }
            }
          });
        </script>

        <p><strong>s2_rr = </strong> <?= $resultado['s2_rr'] ?></p>
        <p><strong>s2_rc = </strong> <?= $resultado['s2_rc'] ?></p>
        <p><strong>s_rr = </strong> <?= $resultado['s_rr'] ?></p>
        <p><strong>s_rc = </strong> <?= $resultado['s_rc'] ?></p>
        <p><strong>r0 = </strong> <?= $resultado['r0'] ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<script>
  const radios = document.querySelectorAll('input[name="idArticulo"]');
  const btnCalcular = document.getElementById('btnCalcularDemanda');

  radios.forEach(radio => {
    radio.addEventListener('change', () => {
      if (document.querySelector('input[name="idArticulo"]:checked')) {
        btnCalcular.disabled = false;
      }
    });
  });

  const form = document.querySelector('form');
  const tipoPrediccionSelect = document.querySelector('select[name="tipoPrediccion"]');
  const alfaInputDiv = document.getElementById('alfaInput');
  const alfaInput = document.querySelector('input[name="alfa"]');

  tipoPrediccionSelect.addEventListener('change', function () {
    alfaInputDiv.style.display = this.value === '3' ? 'block' : 'none';
  });

  form.addEventListener('submit', function (err) {
    const tipoPrediccion = tipoPrediccionSelect.value;
    if (tipoPrediccion === '3') {
      const alfa = parseFloat(alfaInput.value);
      if (isNaN(alfa) || alfa < 0 || alfa > 1) {
        err.preventDefault();
        alert(' Ingrese un valor de alfa válido ');
        alfaInput.focus();
        return false;
      }
    }
  });
</script>
<?php endif; ?>
