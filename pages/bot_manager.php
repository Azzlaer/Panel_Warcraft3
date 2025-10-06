<?php
require_once __DIR__ . '/../config.php';

$xmlFile  = __DIR__ . '/../bots.xml';
$botsDir  = WC3_BOTS_PATH;

/* ---------- FUNCIONES ---------- */

if (!function_exists('wc3_is_running')) {
    function wc3_is_running($exe) {
        $exe = basename($exe);
        $out = [];
        exec('tasklist /FI "IMAGENAME eq ' . $exe . '" /FO CSV /NH', $out);
        foreach ($out as $line) {
            $cols = str_getcsv($line);
            if (isset($cols[0]) && strcasecmp(trim($cols[0], '"'), $exe) === 0) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('wc3_listar_bots_xml')) {
    function wc3_listar_bots_xml($xmlPath) {
        $bots = [];
        if (file_exists($xmlPath)) {
            $xml = simplexml_load_file($xmlPath);
            foreach ($xml->bot as $b) {
                $bots[] = [
                    'name'    => (string)$b->nombre,
                    'exe'     => WC3_BOTS_PATH . DIRECTORY_SEPARATOR . (string)$b->proceso,
                    'cfg'     => WC3_BOTS_PATH . DIRECTORY_SEPARATOR . (string)$b->cfg,
                    'running' => wc3_is_running((string)$b->proceso)
                ];
            }
        }
        return $bots;
    }
}

/* ---------- PETICIONES AJAX ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // === INICIAR BOT SOLO UNA VEZ ===
    if (isset($_POST['start_bot'])) {
        $exe = basename($_POST['start_bot']);
        $exePath = realpath(WC3_BOTS_PATH . DIRECTORY_SEPARATOR . $exe);

        if (!$exePath || !file_exists($exePath)) {
            echo json_encode(['error' => 'No se encontró el ejecutable']);
            exit;
        }

        // 1️⃣ Verificación previa
        if (wc3_is_running($exe)) {
            echo json_encode(['success' => false, 'msg' => 'El bot ya está en ejecución.']);
            exit;
        }

        // 2️⃣ Bloqueo para evitar clics simultáneos
        $lock = fopen(sys_get_temp_dir() . '/wc3bot_' . md5($exe) . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            echo json_encode(['success' => false, 'msg' => 'Otra operación está en curso, inténtelo en unos segundos.']);
            exit;
        }

        // 3️⃣ Lanzar el proceso
        chdir(WC3_BOTS_PATH);
        proc_open('cmd /c start "" "' . $exePath . '"', [], $pipes);

        // 4️⃣ Verificación posterior (pequeña espera para que Windows lo registre)
        sleep(1);
        if (!wc3_is_running($exe)) {
            echo json_encode(['success' => false, 'msg' => 'No se pudo iniciar el bot.']);
        } else {
            echo json_encode(['success' => true, 'msg' => 'Bot iniciado correctamente.']);
        }

        flock($lock, LOCK_UN);
        fclose($lock);
        exit;
    }

    // === DETENER BOT ===
    if (isset($_POST['kill_bot'])) {
        $exe = basename($_POST['kill_bot']);
        exec('taskkill /IM ' . escapeshellarg($exe) . ' /F', $out, $ret);
        echo json_encode([
            'success' => $ret === 0,
            'msg' => $ret === 0 ? 'Bot detenido correctamente.' : 'No se pudo detener el bot.'
        ]);
        exit;
    }

    // === CFG Leer/Guardar ===
    if (isset($_POST['read_cfg'])) {
        $file = realpath($_POST['read_cfg']);
        if ($file && strpos($file, realpath($botsDir)) === 0 && file_exists($file)) {
            echo json_encode(['content' => file_get_contents($file)]);
        } else echo json_encode(['error' => 'Archivo no válido']);
        exit;
    }
    if (isset($_POST['save_cfg'], $_POST['content'])) {
        $file = realpath($_POST['save_cfg']);
        if ($file && strpos($file, realpath($botsDir)) === 0 && file_exists($file)) {
            file_put_contents($file, $_POST['content']);
            echo json_encode(['success' => true, 'msg' => 'CFG guardado correctamente.']);
        } else echo json_encode(['error' => 'Archivo no válido']);
        exit;
    }

    // === Eliminar del XML ===
    if (isset($_POST['delete_bot'])) {
        $name = $_POST['delete_bot'];
        if (file_exists($xmlFile)) {
            $xml = simplexml_load_file($xmlFile);
            foreach ($xml->bot as $i => $b) {
                if ((string)$b->nombre === $name) {
                    unset($xml->bot[$i]);
                    $xml->asXML($xmlFile);
                    echo json_encode(['success' => true, 'msg' => 'Bot eliminado correctamente.']);
                    exit;
                }
            }
            echo json_encode(['error' => 'Bot no encontrado en XML']);
        } else echo json_encode(['error' => 'No existe archivo XML']);
        exit;
    }

    // === Chequear estado ===
    if (isset($_POST['status_check'])) {
        $exe = basename($_POST['status_check']);
        echo json_encode(['running' => wc3_is_running($exe)]);
        exit;
    }
}

/* ---------- DATOS PARA LA TABLA ---------- */
$bots = wc3_listar_bots_xml($xmlFile);
usort($bots, fn($a, $b) => strcmp($a['name'], $b['name']));
?>

<div class="container py-4">
  <h2 class="mb-4">Gestión de Bots WC3 (XML)</h2>
  <div id="msgGlobal" class="alert d-none"></div>

  <div class="table-responsive">
    <table class="table table-dark table-striped align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Proceso (EXE)</th>
          <th>CFG</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bots)): ?>
          <tr><td colspan="5" class="text-center">No hay bots registrados en bots.xml</td></tr>
        <?php else: foreach ($bots as $bot): ?>
          <tr data-exe="<?= htmlspecialchars(basename($bot['exe'])) ?>">
            <td><?= htmlspecialchars($bot['name']) ?></td>
            <td><?= htmlspecialchars(basename($bot['exe'])) ?></td>
            <td><?= htmlspecialchars(basename($bot['cfg'])) ?></td>
            <td class="estado">
              <?= $bot['running']
                ? '<span class="badge bg-success">En ejecución</span>'
                : '<span class="badge bg-secondary">Detenido</span>' ?>
            </td>
            <td>
              <?php if ($bot['running']): ?>
                <button class="btn btn-sm btn-danger stop-bot">Detener</button>
              <?php else: ?>
                <button class="btn btn-sm btn-success start-bot">Iniciar</button>
              <?php endif; ?>
              <button class="btn btn-sm btn-info edit-cfg" data-file="<?= htmlspecialchars($bot['cfg']) ?>">Editar CFG</button>
              <button class="btn btn-sm btn-warning delete-bot" data-name="<?= htmlspecialchars($bot['name']) ?>">Eliminar</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal edición CFG -->
<div class="modal fade" id="cfgModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Editar CFG</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <textarea id="cfgContent" class="form-control bg-secondary text-light" rows="20"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary" id="saveCfg">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
function showMessage(text,type='info'){
  const box=$('#msgGlobal');
  box.removeClass('d-none alert-info alert-danger alert-success')
     .addClass('alert-'+type).text(text);
  setTimeout(()=>box.addClass('d-none'),4000);
}
function updateRowStatus(row,run){
  const e=row.find('.estado');
  if(run){
    e.html('<span class="badge bg-success">En ejecución</span>');
    row.find('.start-bot').remove();
    if(!row.find('.stop-bot').length)
      row.find('td:last').prepend('<button class="btn btn-sm btn-danger stop-bot">Detener</button> ');
  }else{
    e.html('<span class="badge bg-secondary">Detenido</span>');
    row.find('.stop-bot').remove();
    if(!row.find('.start-bot').length)
      row.find('td:last').prepend('<button class="btn btn-sm btn-success start-bot">Iniciar</button> ');
  }
}
$(function(){
  $(document).on('click','.start-bot',function(){
    const r=$(this).closest('tr'),exe=r.data('exe');
    $.post('pages/bot_manager.php',{start_bot:exe},res=>{
      showMessage(res.msg||res.error||'Error',res.success?'success':'danger');
      if(res.success) setTimeout(()=>checkStatus(r,exe),1500);
    },'json');
  });
  $(document).on('click','.stop-bot',function(){
    const r=$(this).closest('tr'),exe=r.data('exe');
    $.post('pages/bot_manager.php',{kill_bot:exe},res=>{
      showMessage(res.msg||res.error||'Error',res.success?'success':'danger');
      if(res.success) setTimeout(()=>checkStatus(r,exe),1500);
    },'json');
  });
  $('.edit-cfg').click(function(){
    const f=$(this).data('file');
    $.post('pages/bot_manager.php',{read_cfg:f},res=>{
      if(res.content!==undefined){
        $('#cfgContent').val(res.content);
        $('#saveCfg').data('file',f);
        $('#cfgModal').modal('show');
      }else showMessage(res.error||'Error al leer CFG','danger');
    },'json');
  });
  $('#saveCfg').click(function(){
    const f=$(this).data('file'),c=$('#cfgContent').val();
    $.post('pages/bot_manager.php',{save_cfg:f,content:c},res=>{
      showMessage(res.msg||res.error||'Error',res.success?'success':'danger');
      if(res.success) $('#cfgModal').modal('hide');
    },'json');
  });
  $('.delete-bot').click(function(){
    if(!confirm('¿Eliminar este bot del XML?')) return;
    const n=$(this).data('name'),r=$(this).closest('tr');
    $.post('pages/bot_manager.php',{delete_bot:n},res=>{
      showMessage(res.msg||res.error||'Error',res.success?'success':'danger');
      if(res.success) r.remove();
    },'json');
  });
  function checkStatus(r,e){
    $.post('pages/bot_manager.php',{status_check:e},res=>{
      if(res.running!==undefined) updateRowStatus(r,res.running);
    },'json');
  }
});
</script>
