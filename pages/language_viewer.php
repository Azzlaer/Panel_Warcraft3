<?php
require_once __DIR__ . '/../config.php';

// Determinar archivo según configuración
$currentLang = LANG_DEFAULT;
$filePath = rtrim(LANG_FOLDER, '\\/') . DIRECTORY_SEPARATOR . 'language_' . $currentLang . '.cfg';
$message = '';

// Verificar existencia del archivo
if (!file_exists($filePath)) {
    $message = "<div class='alert alert-danger'>No se encontró el archivo de idioma: <b>$filePath</b></div>";
    $content = '';
} else {
    $content = file_get_contents($filePath);
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $newContent = $_POST['content'] ?? '';
    if (file_put_contents($filePath, $newContent) !== false) {
        $message = "<div class='alert alert-success'>Archivo guardado correctamente.</div>";
        $content = $newContent;
    } else {
        $message = "<div class='alert alert-danger'>Error al guardar el archivo.</div>";
    }
}
?>

<div class="container py-4 text-light">
  <h2 class="mb-3">🌐 Archivo de Lenguaje</h2>
  <?= $message ?>

  <p class="text-white mb-3">
    Estás editando el archivo:
    <code>language_<?= htmlspecialchars($currentLang) ?>.cfg</code><br>
    <small class="text-secondary">Ruta completa: <?= htmlspecialchars($filePath) ?></small>
  </p>

  <form method="post">
    <div class="mb-3">
      <textarea name="content" class="form-control bg-dark text-white"
                style="height: 500px; font-family: monospace;"><?= htmlspecialchars($content) ?></textarea>
    </div>
    <button class="btn btn-success" type="submit" name="save">💾 Guardar</button>
  </form>
</div>
