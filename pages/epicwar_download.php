<?php
require_once __DIR__ . '/../config.php';

// Siempre iniciar sesión al principio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$historyFile = __DIR__ . '/../data/maps_history.xml';
if (!file_exists(dirname($historyFile))) {
    mkdir(dirname($historyFile), 0777, true);
}

// Inicializar historial
if (!file_exists($historyFile)) {
    $xml = new SimpleXMLElement('<maps></maps>');
    $xml->asXML($historyFile);
}

// Función: agregar al historial
function addToHistory($url, $filename) {
    global $historyFile;
    $xml = simplexml_load_file($historyFile);
    $entry = $xml->addChild('map');
    $entry->addChild('url', $url);
    $entry->addChild('file', $filename);
    $entry->addChild('date', date('Y-m-d H:i:s'));

    if (count($xml->map) > 10) {
        unset($xml->map[0]);
    }
    $xml->asXML($historyFile);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['map_url'])) {
    $mapUrl = trim($_POST['map_url']);
    if (preg_match('#^https://www\.epicwar\.com/maps/(\d+)/?#', $mapUrl, $m)) {
        $id = $m[1];
        $html = @file_get_contents($mapUrl);
        if ($html && preg_match('#https://www\.epicwar\.com/maps/download/\d+/[a-f0-9]+/(.+\.w3x)#i', $html, $m2)) {
            $downloadUrl = $m2[0];
            $filename    = urldecode($m2[1]);
            $savePath    = rtrim(WC3_MAPS_PATH, '\\/') . DIRECTORY_SEPARATOR . $filename;

            // Guardamos info en sesión para que download_worker.php haga la descarga
            $_SESSION['download_url']  = $downloadUrl;
            $_SESSION['download_file'] = $savePath;
            $_SESSION['map_page']      = $mapUrl;
            $_SESSION['map_name']      = $filename;

            // Redirige correctamente usando el sistema de páginas
            header("Location: index.php?page=epicwar_download&downloading=1");
            exit;
        } else {
            $message = "<div class='alert alert-warning'>No se pudo extraer el enlace de descarga.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>URL inválida. Ejemplo: https://www.epicwar.com/maps/347011/</div>";
    }
}

// Historial
$history = simplexml_load_file($historyFile);
?>
<div class="container py-4 text-light">
  <h2 class="mb-3">📥 Sube tu mapa de EpicWar</h2>
  <?= $message ?>

  <?php if (isset($_GET['downloading'])): ?>
    <div class="alert alert-info">Descargando mapa <b><?= htmlspecialchars($_SESSION['map_name'] ?? '') ?></b>...</div>
    <div class="progress mb-3" style="height:25px;">
      <div id="bar" class="progress-bar progress-bar-striped progress-bar-animated" 
           role="progressbar" style="width:0%">0%</div>
    </div>
    <script>
    const es = new EventSource("download_worker.php");
    es.onmessage = function(e){
      if(e.data >= 100){
        document.getElementById('bar').style.width="100%";
        document.getElementById('bar').innerText="Completado";
        es.close();
        setTimeout(()=>location.href="index.php?page=epicwar_download&done=1",1500);
      } else {
        document.getElementById('bar').style.width=e.data+"%";
        document.getElementById('bar').innerText=e.data+"%";
      }
    };
    </script>
  <?php endif; ?>

  <?php if (isset($_GET['done'])): ?>
    <div class="alert alert-success">Mapa descargado: <b><?= htmlspecialchars($_SESSION['map_name'] ?? '') ?></b></div>
    <?php 
      if (!empty($_SESSION['map_page']) && !empty($_SESSION['map_name'])) {
        addToHistory($_SESSION['map_page'], $_SESSION['map_name']); 
      }
      unset($_SESSION['download_url'],$_SESSION['download_file'],$_SESSION['map_page'],$_SESSION['map_name']);
    ?>
  <?php endif; ?>

  <form method="post" class="mb-4">
    <div class="input-group">
      <input type="url" name="map_url" class="form-control" placeholder="https://www.epicwar.com/maps/347011/" required>
      <button class="btn btn-primary" type="submit">Descargar</button>
    </div>
  </form>

  <h4>🕑 Últimos 10 mapas descargados</h4>
  <?php if (count($history->map) === 0): ?>
    <div class="alert alert-info">No hay mapas descargados aún.</div>
  <?php else: ?>
    <table class="table table-dark table-bordered">
      <thead><tr><th>Fecha</th><th>Archivo</th><th>URL</th></tr></thead>
      <tbody>
        <?php foreach (array_reverse($history->map) as $map): ?>
          <tr>
            <td><?= htmlspecialchars($map->date) ?></td>
            <td><?= htmlspecialchars($map->file) ?></td>
            <td><a href="<?= htmlspecialchars($map->url) ?>" target="_blank" class="link-light">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
