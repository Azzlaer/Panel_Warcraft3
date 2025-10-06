<?php
require_once __DIR__ . '/../config.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Para nginx, evita el buffer

// Verificar datos en sesión
if (empty($_SESSION['download_url']) || empty($_SESSION['download_file'])) {
    echo "data: 100\n\n";
    flush();
    exit;
}

$url  = $_SESSION['download_url'];
$file = $_SESSION['download_file'];

// Crear directorio si no existe
$dir = dirname($file);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Descargar con progreso
$ch = curl_init($url);
$fp = fopen($file, 'w+');
if (!$fp) {
    echo "data: 100\n\n";
    flush();
    exit;
}

curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_NOPROGRESS, false);
curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $download_size, $downloaded, $upload_size, $uploaded) {
    if ($download_size > 0) {
        $progress = round(($downloaded / $download_size) * 100);
        echo "data: $progress\n\n";
        ob_flush();
        flush();
    }
});
curl_exec($ch);
curl_close($ch);
fclose($fp);

// Finalizar
echo "data: 100\n\n";
flush();
exit;
