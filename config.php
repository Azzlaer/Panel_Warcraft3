<?php
/**
 * config.php
 * Configuración principal del Panel 7 Days to Die
 */

// ---- INICIO DE SESIÓN ----
if (session_status() === PHP_SESSION_NONE) {
    // Ajusta el nombre de la sesión si quieres aislarla
    session_name('panel_pruebas');
    session_start();
}


define('ADMIN_USER', getenv('ADMIN_USER') ?: 'Azzlaer');
define('ADMIN_PASS', getenv('ADMIN_PASS') ?: '35027595');
define('FOOTER_TEXT', 'Panel Warcraft 3 Bots © ' . date('Y'));

define('MOTD_FILE',        'C:\\Servidores\\wc3bots\\motd.txt');
define('GAMEOVER_FILE',    'C:\\Servidores\\wc3bots\\gameover.txt');
define('GAMELOADED_FILE',  'C:\\Servidores\\wc3bots\\gameloaded.txt');
define('BOTS_XML_PATH', 'C:\\xampp\\htdocs\panel\bots.xml');



// === Archivos de idioma ===
define('LANG_FOLDER', 'C:\\Servidores\\wc3bots\\');  // Carpeta donde están los .cfg
define('LANG_DEFAULT', 'spanish'); // Idioma por defecto (spanish, german, russian, turkish)


define('WC3_MAPS_PATH', 'C:\\Games\\Warcraft III\\Maps\\Download\\');
define('WC3_MAPCFG_PATH', 'C:\\Servidores\\wc3bots\\mapcfgs\\');


//define('PYTHON_BIN', 'C:\\Users\\Guardia\\AppData\\Local\\python-3.11.0-embed-amd64\\python.exe');
//define('PYTHON_BIN', 'C:\\Users\\Guardia\\AppData\\AppData\\Local\\Programs\\Python\\Python312\\python.exe');

// Rutas para GhostMonitor
define('PYTHON_BIN', 'C:\\Users\\Guardia\\AppData\\Local\\Programs\\Python\\Python312\\python.exe');
define('PY_SCRIPT',  __DIR__ . '/python/ghost_monitor.py');

// Número máximo de líneas de log a mostrar
define('PY_LOG_LINES', 200);



if (!defined('WC3_BOTS_PATH')) define('WC3_BOTS_PATH', 'C:\\Servidores\\wc3bots');
if (!defined('WC3_LOGS_PATH')) define('WC3_LOGS_PATH', 'C:\\Servidores\\logs');

if (!defined('INI_PATH'))      define('INI_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'default_messages.ini');
if (!defined('SETTINGS_JSON')) define('SETTINGS_JSON', __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'settings.json');
if (!defined('PY_SCRIPT'))     define('PY_SCRIPT', __DIR__ . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'ghost_monitor.py');



/**
 * Redirige a una URL de forma segura.
 */
function redirect(string $url) {
    header("Location: " . $url);
    exit;
}

/**
 * Verifica si el usuario está logueado.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['logged_in']);
}
