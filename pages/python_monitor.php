<?php
require_once __DIR__ . '/../config.php';

/* ========= Helpers ========= */
$pyBin  = defined('PYTHON_BIN') ? PYTHON_BIN : 'python';
$pyFile = defined('PY_SCRIPT')  ? PY_SCRIPT  : (__DIR__ . '/../python/ghost_monitor.py');
$lockTitle = 'GhostMonitorLOG'; // Nombre de ventana para taskkill

// Ejecuta comando y retorna salida o "No disponible"
function run_cmd($cmd) {
    @exec($cmd, $out, $ret);
    return $ret === 0 ? implode("\n", $out) : 'No disponible';
}

/* ========= AJAX ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Estado
    if (isset($_POST['status'])) {
        $out = [];
        exec('tasklist /FI "WINDOWTITLE eq ' . $lockTitle . '" /FO CSV /NH', $out);
        $running = false;
        foreach ($out as $l) {
            $cols = str_getcsv($l);
            if (!empty($cols[0])) { $running = true; break; }
        }
        echo json_encode(['running' => $running]);
        exit;
    }

    // Iniciar
    if (isset($_POST['start'])) {
        if (!file_exists($pyFile)) {
            echo json_encode(['error' => 'No se encontró el script Python']);
            exit;
        }
        // evita doble arranque
        $chk = [];
        exec('tasklist /FI "WINDOWTITLE eq ' . $lockTitle . '" /FO CSV /NH', $chk);
        foreach ($chk as $l) {
            $cols = str_getcsv($l);
            if (!empty($cols[0])) {
                echo json_encode(['success'=>false,'msg'=>'Ya está en ejecución']);
                exit;
            }
        }
        // Lanza con título de ventana fijo
        $cmd = 'cmd /c start "' . $lockTitle . '" ' . escapeshellarg($pyBin) . ' -u ' . escapeshellarg($pyFile);
        chdir(dirname($pyFile));
        proc_open($cmd, [], $pipes);
        sleep(1);
        echo json_encode(['success'=>true,'msg'=>'Proceso iniciado']);
        exit;
    }

    // Detener
    if (isset($_POST['stop'])) {
        $out = [];
        exec('taskkill /FI "WINDOWTITLE eq ' . $lockTitle . '" /T /F', $out, $ret);
        echo json_encode([
            'success' => $ret === 0,
            'msg'     => $ret === 0 ? 'Proceso detenido' : 'No se pudo detener'
        ]);
        exit;
    }
}

/* ========= Información de Python ========= */
$pythonVersion = trim(run_cmd('"' . $pyBin . '" --version 2>&1'));

// Si PYTHON_BIN es ruta absoluta, úsala directamente.
// Si solo es "python", intentar resolver con where.
if (preg_match('/[\/\\\\]/', $pyBin)) {
    $pythonPath = $pyBin;
} else {
    $pythonPath = trim(run_cmd('where ' . $pyBin));
}

$pipList = run_cmd('"' . $pyBin . '" -m pip list --format=columns 2>&1');

?>
<div class="container py-4 text-light">
  <h2 class="mb-3">GhostMonitor (Python)</h2>
  <div id="msgPy" class="alert d-none"></div>

  <div class="d-flex flex-wrap gap-2 mb-4">
    <button id="btnStart" class="btn btn-success">Iniciar</button>
    <button id="btnStop"  class="btn btn-danger">Detener</button>
    <button id="btnStat"  class="btn btn-secondary">Estado</button>
  </div>

  <p class="text-white">
    Usa los botones para iniciar, detener o consultar el estado del script
    <code>ghost_monitor.py</code>. El proceso solo se ejecuta cuando pulses “Iniciar”.
  </p>

  <h4 class="mt-4 mb-3">Información de Python</h4>
  <table class="table table-dark table-bordered">
    <tbody>
      <tr><th style="width:220px;">Versión de Python</th><td><?= htmlspecialchars($pythonVersion) ?></td></tr>
      <tr><th>Ruta ejecutable</th><td><pre class="m-0"><?= htmlspecialchars($pythonPath) ?></pre></td></tr>
      <tr><th>Paquetes instalados</th>
          <td><pre class="m-0" style="max-height:250px;overflow:auto;"><?= htmlspecialchars($pipList) ?></pre></td></tr>
    </tbody>
  </table>
</div>

<h4 class="mt-4 mb-3">Consola en vivo (GhostMonitor)</h4>
<pre id="ghostConsole" class="bg-black text-white p-3" style="height:300px; overflow:auto; font-size:14px;"></pre>

<script>
function refreshConsole(){
  $.get('pages/ghost_log.php', function(data){
    let box = $('#ghostConsole');
    box.text(data);
    box.scrollTop(box[0].scrollHeight); // autoscroll abajo
  });
}
// refresca cada 3 segundos
setInterval(refreshConsole, 3000);
$(refreshConsole);
</script>



<script>
function flash(msg,type='info'){
  const box=$('#msgPy');
  box.removeClass('d-none alert-info alert-success alert-danger')
     .addClass('alert alert-'+type).text(msg);
  setTimeout(()=>box.addClass('d-none'),5000);
}
$(function(){
  $('#btnStart').click(()=> $.post('pages/python_monitor.php',{ajax:1,start:1},r=>{
      flash(r.msg||r.error||'Listo', r.success?'success':'danger');
  },'json'));
  $('#btnStop').click(()=> $.post('pages/python_monitor.php',{ajax:1,stop:1},r=>{
      flash(r.msg||r.error||'Listo', r.success?'success':'danger');
  },'json'));
  $('#btnStat').click(()=> $.post('pages/python_monitor.php',{ajax:1,status:1},r=>{
      flash(r.running?'En ejecución':'Detenido', r.running?'success':'info');
  },'json'));
});
</script>
