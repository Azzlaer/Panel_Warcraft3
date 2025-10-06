<?php
require_once __DIR__ . '/../config.php';

/**
 * Lista procesos de Python con ruta completa
 */
function listarProcesosPython() {
    $procesos = [];

    // Primero obtenemos la lista base con tasklist
    @exec('tasklist /FI "IMAGENAME eq python.exe" /FO CSV /NH 2>&1', $out, $ret);
    if ($ret === 0 && is_array($out)) {
        foreach ($out as $line) {
            $cols = str_getcsv($line);
            if (count($cols) >= 2 && stripos($line, 'no hay tareas') === false && trim($cols[0]) !== '') {
                $pid = $cols[1] ?? '';
                $ruta = '';

                // Intentar obtener ruta usando WMIC
                if ($pid) {
                    @exec('wmic process where ProcessId=' . (int)$pid . ' get ExecutablePath /value', $wmicOut);
                    foreach ($wmicOut as $wline) {
                        if (stripos($wline, 'ExecutablePath=') === 0) {
                            $ruta = trim(substr($wline, 15));
                            break;
                        }
                    }
                }

                $procesos[] = [
                    'imagen'  => $cols[0] ?? '',
                    'pid'     => $pid,
                    'sesion'  => $cols[2] ?? '',
                    'num'     => $cols[3] ?? '',
                    'memoria' => $cols[4] ?? '',
                    'ruta'    => $ruta ?: '(no disponible)'
                ];
            }
        }
    }
    return $procesos;
}

// Respuesta AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(listarProcesosPython());
    exit;
}

$procesos = listarProcesosPython();
?>
<div class="container py-4 text-light">
  <h2 class="mb-3">Procesos Python en el Servidor</h2>
  <div id="msgProc" class="alert d-none"></div>

  <button id="btnRefresh" class="btn btn-secondary mb-3">Refrescar lista</button>

  <div id="tablaProcesos">
    <?php if (empty($procesos)): ?>
      <div class="alert alert-info text-center">
        No hay procesos de Python en ejecución.
      </div>
    <?php else: ?>
      <table class="table table-dark table-bordered">
        <thead>
          <tr>
            <th>Imagen</th>
            <th>PID</th>
            <th>Sesión</th>
            <th># Sesión</th>
            <th>Uso Memoria</th>
            <th>Ruta Ejecutable</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($procesos as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['imagen']) ?></td>
            <td><?= htmlspecialchars($p['pid']) ?></td>
            <td><?= htmlspecialchars($p['sesion']) ?></td>
            <td><?= htmlspecialchars($p['num']) ?></td>
            <td><?= htmlspecialchars($p['memoria']) ?></td>
            <td><small><?= htmlspecialchars($p['ruta']) ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
function flash(msg,type='info'){
  const box=$('#msgProc');
  box.removeClass('d-none alert-info alert-success alert-danger')
     .addClass('alert alert-'+type).text(msg);
  setTimeout(()=>box.addClass('d-none'),4000);
}

function refreshProcesos(){
  $.post('pages/python_processes.php',{ajax:1},function(data){
    let html='';
    if(!Array.isArray(data) || data.length===0){
      html='<div class="alert alert-info text-center">No hay procesos de Python en ejecución.</div>';
    } else {
      html='<table class="table table-dark table-bordered"><thead><tr>\
        <th>Imagen</th><th>PID</th><th>Sesión</th><th># Sesión</th><th>Uso Memoria</th><th>Ruta Ejecutable</th></tr></thead><tbody>';
      data.forEach(function(p){
        html+='<tr><td>'+p.imagen+'</td><td>'+p.pid+'</td><td>'+p.sesion+'</td><td>'+p.num+'</td><td>'+p.memoria+'</td><td><small>'+p.ruta+'</small></td></tr>';
      });
      html+='</tbody></table>';
    }
    $('#tablaProcesos').html(html);
    flash('Lista actualizada','success');
  },'json');
}

$(function(){
  $('#btnRefresh').click(refreshProcesos);
});
</script>
