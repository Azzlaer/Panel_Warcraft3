<?php
require_once __DIR__ . '/../config.php';

// Carpeta de destino definida en config.php
$uploadDir = WC3_MAPCFG_PATH;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_FILES['cfgFile']) || $_FILES['cfgFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'Error al recibir el archivo.']);
        exit;
    }

    $fileName = $_FILES['cfgFile']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validar extensión .cfg
    if ($ext !== 'cfg') {
        echo json_encode(['success' => false, 'msg' => 'Solo se permiten archivos .cfg']);
        exit;
    }

    // Nombre seguro
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
    $dest = rtrim($uploadDir, '\\/') . DIRECTORY_SEPARATOR . $safeName;

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($_FILES['cfgFile']['tmp_name'], $dest)) {
        echo json_encode(['success' => true, 'msg' => "Archivo CFG subido: $safeName"]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'No se pudo mover el archivo.']);
    }
    exit;
}
?>
<div class="container py-4 text-white">
  <h2 class="mb-4">Sube archivo CFG de mapa</h2>

  <div id="uploadMsg" class="alert d-none"></div>

  <form id="uploadForm" class="mb-3">
    <div class="mb-3">
      <label for="cfgFile" class="form-label">Selecciona el archivo (.cfg)</label>
      <input type="file" class="form-control" id="cfgFile" name="cfgFile" accept=".cfg" required>
    </div>
    <button type="submit" class="btn btn-primary">Subir CFG</button>
  </form>

  <div class="progress mb-3" style="height: 25px;">
    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%">0%</div>
  </div>

  <p class="text-white">
    Los archivos CFG se guardarán en:
    <code><?= htmlspecialchars($uploadDir) ?></code>
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
  xhr.open('POST', 'pages/upload_cfg.php', true);

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
        $('#cfgFile').val('');
      }
    } else {
      flash('Error en la subida', 'danger');
    }
  };

  xhr.send(formData);
});
</script>
