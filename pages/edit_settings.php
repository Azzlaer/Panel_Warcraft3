<?php
require_once __DIR__ . '/../config.php';

// Fallbacks
$jsonPath = defined('SETTINGS_JSON') ? SETTINGS_JSON : (__DIR__ . '/../data/settings.json');
$logsDir  = defined('WC3_LOGS_PATH') ? WC3_LOGS_PATH : 'C:\\Servidores\\logs';

@mkdir(dirname($jsonPath), 0777, true);
if (!file_exists($jsonPath)) {
    file_put_contents($jsonPath, json_encode([
        "monitors" => [
            ["logfile" => $logsDir . DIRECTORY_SEPARATOR . "d2ibot.log", "webhook" => ""]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

// Leer settings
$settings = json_decode(@file_get_contents($jsonPath), true);
if (!is_array($settings)) $settings = ["monitors" => []];
if (!isset($settings['monitors']) || !is_array($settings['monitors'])) $settings['monitors'] = [];

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $items = $_POST['items'] ?? [];
    $settings['monitors'] = [];
    foreach ($items as $it) {
        $log = trim($it['logfile'] ?? '');
        $wh  = trim($it['webhook'] ?? '');
        if ($log) $settings['monitors'][] = ["logfile"=>$log, "webhook"=>$wh];
    }
    file_put_contents($jsonPath, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success'=>true, 'msg'=>'settings.json actualizado']);
    exit;
}
?>
<div class="container py-4">
  <h2 class="mb-3">Editar settings.json</h2>
  <div id="msgSet" class="alert d-none"></div>

  <form id="formSettings" class="bg-dark p-4 rounded text-light">
    <p class="text-white">Define uno o varios pares LOG/Webhook. El script Python leerá este archivo.</p>

    <div id="items">
      <?php if (empty($settings['monitors'])): ?>
        <div class="row g-2 mb-2 item">
          <div class="col-md-6">
            <input name="items[0][logfile]" class="form-control bg-secondary text-light"
                   placeholder="Ruta al .log (<?= htmlspecialchars($logsDir) ?>\*.log)">
          </div>
          <div class="col-md-6">
            <input name="items[0][webhook]" class="form-control bg-secondary text-light"
                   placeholder="URL webhook">
          </div>
        </div>
      <?php else: foreach ($settings['monitors'] as $i=>$m): ?>
        <div class="row g-2 mb-2 item">
          <div class="col-md-6">
            <input name="items[<?= $i ?>][logfile]" class="form-control bg-secondary text-light"
                   value="<?= htmlspecialchars($m['logfile']) ?>">
          </div>
          <div class="col-md-6">
            <input name="items[<?= $i ?>][webhook]" class="form-control bg-secondary text-light"
                   value="<?= htmlspecialchars($m['webhook']) ?>">
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <button type="button" id="addItem" class="btn btn-sm btn-outline-light mb-3">Añadir fila</button>
    <button class="btn btn-primary">Guardar settings.json</button>
  </form>
</div>

<script>
$(function(){
  $('#addItem').on('click', function(){
    const idx = $('#items .item').length;
    $('#items').append(
      '<div class="row g-2 mb-2 item">\
        <div class="col-md-6"><input name="items['+idx+'][logfile]" class="form-control bg-secondary text-light" placeholder="Ruta al .log"></div>\
        <div class="col-md-6"><input name="items['+idx+'][webhook]" class="form-control bg-secondary text-light" placeholder="URL webhook"></div>\
      </div>'
    );
  });

  $('#formSettings').on('submit', function(e){
    e.preventDefault();
    $.post('pages/edit_settings.php', $(this).serialize(), function(res){
      const box = $('#msgSet');
      box.removeClass('d-none alert-danger').addClass('alert alert-success').text(res.msg||'Guardado');
      setTimeout(()=>box.addClass('d-none'),3000);
    }, 'json').fail(function(){
      $('#msgSet').removeClass('d-none alert-success').addClass('alert alert-danger').text('Error guardando');
    });
  });
});
</script>
