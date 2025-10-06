<?php
require_once __DIR__ . '/../config.php';

$xmlFile = __DIR__ . '/../bots.xml';

// ---- Peticiones AJAX ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $botName = trim($_POST['bot'] ?? '');
    $logFile = trim($_POST['logfile'] ?? '');

    if (!$botName || !$logFile) {
        echo json_encode(['success' => false, 'msg' => 'Debes seleccionar un bot y escribir el archivo de log.']);
        exit;
    }
    if (!file_exists($xmlFile)) {
        echo json_encode(['success' => false, 'msg' => 'No se encontró bots.xml.']);
        exit;
    }

    $xml = simplexml_load_file($xmlFile);
    $found = false;
    foreach ($xml->bot as $b) {
        if ((string)$b->nombre === $botName) {
            if (!isset($b->log)) {
                $b->addChild('log', $logFile);
            } else {
                $b->log = $logFile;
            }
            $found = true;
            break;
        }
    }

    if ($found) {
        $xml->asXML($xmlFile);
        echo json_encode(['success' => true, 'msg' => "Log agregado/actualizado para $botName"]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Bot no encontrado en XML.']);
    }
    exit;
}

// ---- Cargar nombres de bots para el select ----
$botNames = [];
if (file_exists($xmlFile)) {
    $xml = simplexml_load_file($xmlFile);
    foreach ($xml->bot as $b) {
        $botNames[] = (string)$b->nombre;
    }
}
?>

<div class="container py-4">
  <h2 class="mb-4">Agregar / Actualizar Log de Bot</h2>

  <?php if (empty($botNames)): ?>
    <div class="alert alert-warning">No hay bots en bots.xml. Agrega bots primero.</div>
  <?php else: ?>
    <div id="msgBox" class="alert d-none"></div>
    <form id="logForm" class="bg-dark p-4 rounded text-light">
      <div class="mb-3">
        <label class="form-label">Selecciona el Bot</label>
        <select name="bot" class="form-select" required>
          <option value="">-- Elige un bot --</option>
          <?php foreach ($botNames as $name): ?>
            <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Nombre del archivo de log</label>
        <input type="text" name="logfile" class="form-control"
               placeholder="ej: d2ibot.log" required>
      </div>

      <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
  <?php endif; ?>
</div>

<script>
$(function(){
  $('#logForm').on('submit', function(e){
    e.preventDefault();
    const data = $(this).serialize() + '&ajax=1';
    $.post('pages/add_log.php', data, function(res){
      const box = $('#msgBox');
      box.removeClass('d-none alert-success alert-danger');
      if(res.success){
        box.addClass('alert-success').text(res.msg);
        $('#logForm')[0].reset();
      } else {
        box.addClass('alert-danger').text(res.msg || 'Error desconocido');
      }
      setTimeout(()=> box.addClass('d-none'), 4000);
    }, 'json');
  });
});
</script>
