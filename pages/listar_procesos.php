<?php
require_once __DIR__ . '/../config.php';

// === ARCHIVO XML DE BOTS ===
$xmlPath = defined('BOTS_XML_PATH') ? BOTS_XML_PATH : 'C:\\Servidores\\wc3bots\\bots.xml';

if (!file_exists($xmlPath)) {
    echo "<div class='alert alert-danger m-4'>❌ No se encontró el archivo <code>$xmlPath</code></div>";
    exit;
}

// === CARGAR LOS PROCESOS DEFINIDOS EN EL XML ===
$xml = simplexml_load_file($xmlPath);
$procesos = [];

foreach ($xml->bot as $bot) {
    $nombre = (string)$bot->nombre;
    $proceso = (string)$bot->proceso;
    if (trim($proceso) !== '') {
        $procesos[] = [
            'nombre'  => $nombre,
            'proceso' => basename(trim($proceso))
        ];
    }
}

// === OBTENER LISTADO DE PROCESOS ACTIVOS DEL SISTEMA ===
function obtenerProcesosActivos() {
    @exec('tasklist /FO CSV /NH', $out, $ret);
    $lista = [];
    if ($ret === 0 && is_array($out)) {
        foreach ($out as $line) {
            $cols = str_getcsv($line);
            if (count($cols) >= 5) {
                $imagen   = strtolower(trim($cols[0]));
                $pid      = trim($cols[1]);
                $sesion   = trim($cols[2]);
                $num      = trim($cols[3]);
                $memoria  = trim($cols[4]);
                $lista[$imagen] = [
                    'imagen'  => $cols[0],
                    'pid'     => $pid,
                    'sesion'  => $sesion,
                    'num'     => $num,
                    'memoria' => $memoria,
                    'ruta'    => obtenerRutaProceso($pid)
                ];
            }
        }
    }
    return $lista;
}

// === OBTENER RUTA COMPLETA DE UN PROCESO ===
function obtenerRutaProceso($pid) {
    if (!$pid) return 'No disponible';
    @exec('wmic process where processid=' . intval($pid) . ' get ExecutablePath /value 2>&1', $out);
    foreach ($out as $line) {
        if (stripos($line, 'ExecutablePath=') === 0) {
            return trim(str_replace('ExecutablePath=', '', $line));
        }
    }
    return 'No disponible';
}

// === CONSULTAR PROCESOS ACTIVOS ===
$activos = obtenerProcesosActivos();
?>

<div class="container py-4 text-light">
  <h2 class="mb-4">⚙️ Procesos Definidos en bots.xml</h2>

  <?php if (empty($procesos)): ?>
    <div class="alert alert-warning">No se encontraron procesos definidos en <b>bots.xml</b>.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-dark table-bordered align-middle">
        <thead>
          <tr>
            <th>Nombre del Bot</th>
            <th>Imagen</th>
            <th>PID</th>
            <th>Sesión</th>
            <th># Sesión</th>
            <th>Uso Memoria</th>
            <th>Ruta Ejecutable</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($procesos as $p):
              $img = strtolower($p['proceso']);
              $info = $activos[$img] ?? null;
          ?>
          <tr>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <?php if ($info): ?>
              <td><?= htmlspecialchars($info['imagen']) ?></td>
              <td><?= htmlspecialchars($info['pid']) ?></td>
              <td><?= htmlspecialchars($info['sesion']) ?></td>
              <td><?= htmlspecialchars($info['num']) ?></td>
              <td><?= htmlspecialchars($info['memoria']) ?></td>
              <td><pre class="m-0 text-white" style="white-space:normal;"><?= htmlspecialchars($info['ruta']) ?></pre></td>
              <td><span class="badge bg-success">🟢 En ejecución</span></td>
            <?php else: ?>
              <td colspan="6" class="text-center text-secondary">No se encontró el proceso activo</td>
              <td><span class="badge bg-danger">🔴 Detenido</span></td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="text-end mt-3">
    <button id="btnRefresh" class="btn btn-secondary">🔄 Actualizar</button>
  </div>
</div>

<script>
$(function(){
  $('#btnRefresh').click(function(){
    $('#main').html('<div class="p-5 text-center text-light">Cargando...</div>');
    $('#main').load('pages/listar_procesos.php');
  });
});
</script>
