<?php
require_once __DIR__ . '/../config.php';

$logFile = __DIR__ . '/../python/ghost_monitor.log';
$maxLines = defined('PY_LOG_LINES') ? PY_LOG_LINES : 200;

header('Content-Type: text/plain; charset=utf-8');

if (!file_exists($logFile)) {
    echo "⚠️ El archivo de log no existe.";
    exit;
}

// Leer archivo completo de forma segura
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    echo "⚠️ No se pudo leer el archivo de log.";
    exit;
}

$total = count($lines);
$slice = array_slice($lines, -$maxLines);

echo implode("\n", $slice);
?>
