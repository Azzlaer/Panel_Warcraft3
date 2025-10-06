<?php
require_once __DIR__ . '/../config.php';

$mapsDir = rtrim(WC3_MAPS_PATH, '\\/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Eliminar archivo
    if (isset($_POST['delete'])) {
        $file = basename($_POST['delete']); // nombre seguro
        $fullPath = $mapsDir . DIRECTORY_SEPARATOR . $file;

        if (file_exists($fullPath) && is_file($fullPath) &&
            preg_match('/\.(w3x|w3m)$/i', $file)) {
            if (@unlink($fullPath)) {
                echo json_encode(['success' => true, 'msg' => "Archivo eliminado: $file"]);
            } else {
                echo json_encode(['success' => false, 'msg' => "No se pudo eliminar $file"]);
            }
        } else {
            echo json_encode(['success' => false, 'msg' => "Archivo no válido"]);
        }
        exit;
    }
}

// Listar archivos
$maps = [];
if (is_dir($mapsDir)) {
    $files = glob($mapsDir . DIRECTORY_SEPARATOR . '*.{w3x,w3m}', GLOB_BRACE);
    foreach ($files as $f) {
        $maps[] = [
            'name' => basename($f),
            'size' => round(filesize($f) / 1048576, 2) // MB
        ];
    }
}
?>
<div class="container py-4 text-light">
  <h2 class="mb-4">Mapas en la carpeta Download</h2>

  <div id="msgMaps" class="alert d-none"></div>

  <div class="table-responsive">
    <table class="table table-dark table-striped align-middle">
      <thead>
        <tr>
          <th>Archivo</th>
          <th>Tamaño (MB)</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($maps)): ?>
          <tr><td colspan="3" class="text-center">No hay mapas en la carpeta</td></tr>
        <?php else: ?>
          <?php foreach ($maps as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['name']) ?></td>
              <td><?= htmlspecialchars($m['size']) ?></td>
              <td>
                <button class="btn btn-sm btn-danger delete-map"
                        data-file="<?= htmlspecialchars($m['name']) ?>">
                        Eliminar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function flash(msg,type='info'){
  const box = $('#msgMaps');
  box.removeClass('d-none alert-success alert-danger alert-info')
     .addClass('alert alert-' + type).text(msg);
  setTimeout(()=> box.addClass('d-none'), 4000);
}

$(function(){
  $('.delete-map').click(function(){
    if(!confirm('¿Seguro que deseas eliminar este archivo?')) return;
    const file = $(this).data('file');
    $.post('pages/list_maps.php', {delete:file}, function(res){
      flash(res.msg, res.success ? 'success' : 'danger');
      if(res.success){
        location.reload();
      }
    }, 'json');
  });
});
</script>
