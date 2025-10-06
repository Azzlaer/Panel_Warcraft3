<?php
require_once __DIR__ . '/../config.php';

$xmlFile = __DIR__ . '/../bots.xml';

// --- Procesar petición AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $nombre  = trim($_POST['nombre'] ?? '');
    $proceso = trim($_POST['proceso'] ?? '');
    $cfg     = trim($_POST['cfg'] ?? '');

    if ($nombre && $proceso && $cfg) {
        // Crear XML si no existe
        if (!file_exists($xmlFile)) {
            $xml = new SimpleXMLElement('<bots/>');
        } else {
            $xml = simplexml_load_file($xmlFile);
        }

        // Agregar bot
        $bot = $xml->addChild('bot');
        $bot->addChild('nombre',  $nombre);
        $bot->addChild('proceso', $proceso);
        $bot->addChild('cfg',     $cfg);

        $xml->asXML($xmlFile);
        echo json_encode(['success' => true, 'msg' => '✅ Se agregó correctamente el bot.']);
    } else {
        echo json_encode(['success' => false, 'msg' => '⚠️ Todos los campos son obligatorios.']);
    }
    exit;
}
?>

<div class="container py-4">
  <h2 class="mb-4">Agregar un nuevo Bot</h2>

  <div id="msgBot" class="alert d-none"></div>

  <form id="formAddBot" class="bg-dark p-4 rounded text-light">
    <div class="mb-3">
      <label class="form-label">Nombre</label>
      <input type="text" name="nombre" class="form-control bg-secondary text-light" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Proceso (exe)</label>
      <input type="text" name="proceso" class="form-control bg-secondary text-light" placeholder="ej: d2ibot.exe" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Archivo CFG</label>
      <input type="text" name="cfg" class="form-control bg-secondary text-light" placeholder="ej: d2ibot.cfg" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar Bot</button>
  </form>
</div>

<script>
$(function(){
  $('#formAddBot').on('submit', function(e){
    e.preventDefault(); // evita refresh
    const datos = $(this).serialize() + '&ajax=1';
    $.post('pages/add_bot.php', datos, function(res){
      const box = $('#msgBot');
      box.removeClass('d-none alert-info alert-danger');
      if(res.success){
        box.addClass('alert-info').text(res.msg);
        $('#formAddBot')[0].reset();
      } else {
        box.addClass('alert-danger').text(res.msg);
      }
    }, 'json');
  });
});
</script>
