<?php
use GuzzleHttp\Client;

$action = $_GET['action'] ?? 'calc';
include __DIR__ . '/../../tabs/demanda-tabs.php';

$client = new Client(['base_uri' => 'http://localhost:5000']);
$articulos = [];
$error = null;
$resultado = null;
$tipoPrediccion = $_POST['tipoPrediccion'] ?? null;
$periodo = $_POST['periodo'] ?? null;
$alfa = $_POST['alfa'] ?? null;
$idArticulo = $_POST['idArticulo'] ?? null;

try {
    $response = $client->get('/MaestroArticulos/articulos/list-art-datos');
    $articulos = json_decode($response->getBody()->getContents(), true);
} catch (Exception $err) {
    $error = $err->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idArticulo && $tipoPrediccion && $periodo) {
    $query = [
        'idArticulo' => $idArticulo,
        'tipoPrediccion' => $tipoPrediccion,
        'periodo' => $periodo
    ];
    if ($tipoPrediccion == 3 && $alfa !== null) {
        $query['alfa'] = $alfa;
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

  <div style="flex-grow: 1; padding-left: 8px;">
    <?php if ($resultado): ?>
      <h3>• Resultados •</h3>
      <p><strong>Demanda estimada = </strong> <?= $resultado['demanda'] ?></p>
      <p><strong>Desviación estandar = </strong> <?= $resultado['desviacionEstandarPeriodo'] ?></p>

      <?php if (!empty($resultado['valoresTabla'])): ?>
        
        <table class="tabla-base">
            <thead>
            <tr><th>Mes</th><th>Cantidad (ventas)</th></tr>
            </thead>
            <tbody>
            <?php
                $totalFilas = count($resultado['valoresTabla']);
                foreach ($resultado['valoresTabla'] as $i => $val):
                $esUltima = ($i === $totalFilas - 1);
            ?>
                <tr class="<?= $esUltima ? 'fila-final-demanda' : '' ?>">
                <td><?= $val['mes'] ?></td>
                <td><?= $val['cantidad'] ?></td>
                </tr>
                <?php if ($esUltima): ?>
                <tr class="leyenda-demanda">
                    <td colspan="2"> Demanda calculada para el próximo periodo</td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>

      <?php elseif (!empty($resultado['puntosXY'])): ?>

        <table class="tabla-base">
          <thead><tr><th>X</th><th>Y Real</th><th>Y Estimado</th></tr></thead>
          <tbody>
            <?php foreach ($resultado['puntosXY'] as $p): ?>
              <tr>
                <td><?= $p['x'] ?></td>
                <td><?= $p['yReal'] ?? '-' ?></td>
                <td><?= $p['yEstimado'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <p><strong>s2rr (Desviación entre la realidad y la Media) = </strong> <?= $resultado['s2rr'] ?></p>
        <p><strong>s2rc (Desvío entre la Predicción y la Media) = </strong> <?= $resultado['s2rc'] ?></p>
        <p><strong>r0 (Coef. de correlación) = </strong> <?= $resultado['r0'] ?></p>
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

  form.addEventListener('submit', function (e) {
    const tipoPrediccion = tipoPrediccionSelect.value;

    if (tipoPrediccion === '3') {
      const alfa = parseFloat(alfaInput.value);

      if (isNaN(alfa) || alfa < 0 || alfa > 1) {
        e.preventDefault();
        alert(' Ingrese un valor de alfa válido ');
        alfaInput.focus();
        return false;
      }
    }
  });

    document.querySelector('select[name="tipoPrediccion"]').addEventListener('change', function() {
    document.getElementById('alfaInput').style.display = this.value == '3' ? 'block' : 'none';
  });
</script>

<?php endif; ?>