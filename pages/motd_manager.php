<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

/* === Configuración === */
$files = [
    'motd'       => ['path' => MOTD_FILE, 'label' => 'Message of the Day (motd.txt)'],
    'gameover'   => ['path' => GAMEOVER_FILE, 'label' => 'Game Over (gameover.txt)'],
    'gameloaded' => ['path' => GAMELOADED_FILE, 'label' => 'Game Loaded (gameloaded.txt)']
];

/* === Funciones de ayuda === */
function read_file_safely($path) {
    return file_exists($path) ? file_get_contents($path) : '';
}
function write_file_safely($path, $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $fp = @fopen($path, 'wb');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }
    fwrite($fp, $content);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/* === AJAX Guardar === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $key = $_POST['file'] ?? '';
    $content = $_POST['content'] ?? '';
    global $files;
    if (!isset($files[$key])) {
        echo json_encode(['ok'=>false, 'error'=>'Archivo no válido']); exit;
    }
    $path = $files[$key]['path'];
    $ok = write_file_safely($path, $content);
    echo json_encode(['ok'=>$ok, 'msg'=>$ok?'Archivo guardado correctamente':'Error al guardar el archivo']);
    exit;
}

/* === Cargar contenido === */
$data = [];
foreach ($files as $k => $info) {
    $data[$k] = read_file_safely($info['path']);
}
?>
<style>
.card-header h5 {
  color: #fff !important;
}
</style>

<div class="container py-4 text-light">
  <h2 class="mb-4">📜 Editor de Mensajes MOTD</h2>

  <p class="text-muted mb-4"><span style="color: #ffffff;">Aqu&iacute; puedes editar los mensajes que se muestran en el juego. Cada uno se guarda en su respectivo archivo dentro de</span> <code>C:\Servidores\wc3bots\</code>.</p>

  <div id="msgMotd" class="alert d-none"></div>

  <?php foreach ($files as $key => $info): ?>
    <div class="card bg-dark mb-4 border-secondary">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0"><?= htmlspecialchars($info['label']) ?></h5>
        <button class="btn btn-success btn-sm btn-save" data-file="<?= $key ?>">💾 Guardar</button>
      </div>
      <div class="card-body">
        <textarea id="txt_<?= $key ?>" class="form-control bg-secondary text-light" 
                  rows="6"><?= htmlspecialchars($data[$key]) ?></textarea>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
function flashMotd(msg, type='info') {
  const box = $('#msgMotd');
  box.removeClass('d-none alert-info alert-success alert-danger')
     .addClass('alert alert-' + (type==='error'?'danger':type))
     .text(msg);
  setTimeout(()=>box.addClass('d-none'),4000);
}

$(function(){
  $('.btn-save').click(function(){
    const key = $(this).data('file');
    const content = $('#txt_' + key).val();
    $.post('pages/motd_manager.php', { ajax:1, file:key, content:content }, function(r){
      if(r.ok) flashMotd(r.msg,'success'); else flashMotd(r.error||'Error','error');
    },'json').fail(()=>flashMotd('Error de conexión','error'));
  });
});
</script>
