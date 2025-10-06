<?php
require_once __DIR__ . '/../config.php';

$xmlFile  = __DIR__ . '/../bots.xml';
$logsDir  = 'C:\\Servidores\\logs';

/* -------- Funciones -------- */
function wc3_listar_bots_xml_con_logs($xmlPath, $logsDir) {
    $bots = [];
    if (file_exists($xmlPath)) {
        $xml = simplexml_load_file($xmlPath);
        foreach ($xml->bot as $b) {
            $logFile = isset($b->log) ? (string)$b->log : '';
            $sizeMB  = 0;
            if ($logFile) {
                $path = $logsDir . DIRECTORY_SEPARATOR . $logFile;
                if (file_exists($path)) {
                    $sizeMB = round(filesize($path) / (1024 * 1024), 2);
                }
            }
            $bots[] = [
                'name' => (string)$b->nombre,
                'log'  => $logFile,
                'size' => $sizeMB
            ];
        }
    }
    return $bots;
}

/* -------- Peticiones AJAX -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Leer log
    if (isset($_POST['read_log'])) {
        $file = basename($_POST['read_log']);
        $path = realpath($logsDir . DIRECTORY_SEPARATOR . $file);
        if ($path && file_exists($path)) {
            echo json_encode(['content' => file_get_contents($path)]);
        } else {
            echo json_encode(['error' => 'Archivo de log no encontrado']);
        }
        exit;
    }

    // Limpiar log
    if (isset($_POST['clear_log'])) {
        $file = basename($_POST['clear_log']);
        $path = realpath($logsDir . DIRECTORY_SEPARATOR . $file);
        if ($path && file_exists($path)) {
            file_put_contents($path, '');
            echo json_encode(['success' => true, 'msg' => 'Log limpiado correctamente.']);
        } else {
            echo json_encode(['error' => 'Archivo de log no encontrado']);
        }
        exit;
    }

    // Eliminar nodo <log> del XML
    if (isset($_POST['delete_log'])) {
        $name = $_POST['delete_log'];
        if (file_exists($xmlFile)) {
            $xml = simplexml_load_file($xmlFile);
            foreach ($xml->bot as $b) {
                if ((string)$b->nombre === $name) {
                    unset($b->log);
                    $xml->asXML($xmlFile);
                    echo json_encode(['success' => true, 'msg' => 'Log eliminado de ' . $name]);
                    exit;
                }
            }
            echo json_encode(['error' => 'No se encontró el bot en XML']);
        } else {
            echo json_encode(['error' => 'No existe bots.xml']);
        }
        exit;
    }
}

$bots = wc3_listar_bots_xml_con_logs($xmlFile, $logsDir);
usort($bots, fn($a, $b) => strcmp($a['name'], $b['name']));
?>

<div class="container py-4">
  <h2 class="mb-4">Logs de Bots WC3</h2>
  <div id="msgGlobal" class="alert d-none"></div>

  <div class="table-responsive">
    <table class="table table-dark table-striped align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Archivo Log</th>
          <th>Tamaño (MB)</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bots)): ?>
          <tr><td colspan="4" class="text-center">No hay bots con logs en bots.xml</td></tr>
        <?php else: foreach ($bots as $bot): ?>
          <tr data-name="<?= htmlspecialchars($bot['name']) ?>">
            <td><?= htmlspecialchars($bot['name']) ?></td>
            <td><?= htmlspecialchars($bot['log']) ?></td>
            <td><?= $bot['log'] ? $bot['size'] . ' MB' : '-' ?></td>
            <td>
              <?php if ($bot['log']): ?>
                <button class="btn btn-sm btn-info view-log"
                        data-file="<?= htmlspecialchars($bot['log']) ?>">Ver</button>
                <button class="btn btn-sm btn-warning clear-log"
                        data-file="<?= htmlspecialchars($bot['log']) ?>">Limpiar</button>
                <button class="btn btn-sm btn-danger delete-log"
                        data-name="<?= htmlspecialchars($bot['name']) ?>">Eliminar</button>
              <?php else: ?>
                <span class="text-muted">Sin log</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal para ver log -->
<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Contenido del Log</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <pre id="logContent" style="white-space: pre-wrap; background:#1e1e1e; padding:1rem; max-height:70vh;"></pre>
      </div>
    </div>
  </div>
</div>

<script>
let logInterval=null;

function showMessage(text,type='info'){
  const box=$('#msgGlobal');
  box.removeClass('d-none alert-info alert-danger alert-success')
     .addClass('alert-'+type).text(text);
  setTimeout(()=>box.addClass('d-none'),4000);
}

$(function(){
  // Ver log
  $(document).on('click','.view-log',function(){
    const file=$(this).data('file');
    $('#logContent').text('Cargando...');
    $('#logModal').modal('show');
    if(logInterval) clearInterval(logInterval);
    const loadLog=()=>{
      $.post('pages/logs.php',{read_log:file},res=>{
        if(res.content!==undefined){
          $('#logContent').text(res.content);
        } else $('#logContent').text(res.error||'Error al leer log');
      },'json');
    };
    loadLog();
    logInterval=setInterval(loadLog,2000);
  });
  $('#logModal').on('hidden.bs.modal',()=>{ if(logInterval) clearInterval(logInterval); });

  // Limpiar log
  $(document).on('click','.clear-log',function(){
    const file=$(this).data('file');
    if(!confirm('¿Seguro que deseas limpiar este log?')) return;
    $.post('pages/logs.php',{clear_log:file},res=>{
      showMessage(res.msg||res.error||'Error',res.success?'success':'danger');
    },'json');
  });

  // Eliminar log del XML
  $(document).on('click','.delete-log',function(){
    if(!confirm('¿Eliminar este log del XML?')) return;
    const name=$(this).data('name');
    const row=$(this).closest('tr');
    $.post('pages/logs.php',{delete_log:name},res=>{
      if(res.success){
        showMessage(res.msg,'success');
        row.remove();
      } else showMessage(res.error||'Error al eliminar','danger');
    },'json');
  });
});
</script>
