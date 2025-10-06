<?php
require_once __DIR__ . '/../config.php';

// Ruta definida en config.php
$uploadDir = WC3_MAPS_PATH;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_FILES['mapFile']) || $_FILES['mapFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'Error al recibir el archivo.']);
        exit;
    }

    $fileName = $_FILES['mapFile']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validar extensión
    if (!in_array($ext, ['w3x', 'w3m'])) {
        echo json_encode(['success' => false, 'msg' => 'Solo se permiten archivos .w3x o .w3m']);
        exit;
    }

    // Nombre seguro
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
    $dest = rtrim($uploadDir, '\\/') . DIRECTORY_SEPARATOR . $safeName;

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($_FILES['mapFile']['tmp_name'], $dest)) {
        echo json_encode(['success' => true, 'msg' => "Mapa subido correctamente: $safeName"]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se pudo mover el archivo.']);
    }
    exit;
}
?>
<div class="container py-4 text-light">
  <h2 class="mb-4">Sube tu mapa</h2>

  <div id="uploadMsg" class="alert d-none"></div>

  <form id="uploadForm" class="mb-3">
    <div class="mb-3">
      <label for="mapFile" class="form-label">Selecciona tu archivo (.w3x o .w3m)</label>
      <input type="file" class="form-control" id="mapFile" name="mapFile" accept=".w3x,.w3m" required>
    </div>
    <button type="submit" class="btn btn-primary">Subir Mapa</button>
  </form>

  <div class="progress mb-3" style="height: 25px;">
    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
  </div>

  <p class="text-muted">
    Los mapas se guardarán en: <code><?= htmlspecialchars($uploadDir) ?></code>
  </p>
</div>

<script>
function flash(msg,type='info'){
  const box = $('#uploadMsg');
  box.removeClass('d-none alert-success alert-danger alert-info')
     .addClass('alert alert-' + type).text(msg);
  setTimeout(()=> box.addClass('d-none'), 5000);
}

$('#uploadForm').on('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'pages/upload_map.php', true);

  xhr.upload.addEventListener('progress', function(e){
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      $('#progressBar').css('width', percent + '%').text(percent + '%');
    }
  });

  xhr.onload = function(){
    if (xhr.status === 200) {
      const res = JSON.parse(xhr.responseText);
      flash(res.msg, res.success ? 'success' : 'danger');
      if (res.success) {
        $('#progressBar').css('width', '100%').text('100%');
        $('#mapFile').val('');
      }
    } else {
      flash('Error en la subida', 'danger');
    }
  };

  xhr.send(formData);
});
</script>
