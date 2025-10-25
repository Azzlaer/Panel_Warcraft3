<?php
require_once __DIR__ . '/../config.php';

// === FUNCIONES AUXILIARES ===
function leerArchivo($ruta) {
    return file_exists($ruta) ? htmlspecialchars(file_get_contents($ruta)) : "⚠️ No se encontró el archivo.";
}
function guardarArchivo($ruta, $contenido) {
    return file_exists($ruta) ? file_put_contents($ruta, $contenido) !== false : false;
}
function limpiarArchivo($ruta) {
    return file_exists($ruta) && is_writable($ruta) ? file_put_contents($ruta, "") !== false : false;
}
function ejecutarArchivo($ruta) {
    if (file_exists($ruta)) {
        pclose(popen("start /B " . escapeshellarg($ruta), "r"));
        return true;
    }
    return false;
}
function detenerProceso($nombre) {
    exec("taskkill /F /IM " . escapeshellarg($nombre) . " 2>nul", $out, $code);
    return $code === 0;
}
function procesoActivo($nombre) {
    exec("tasklist | findstr /I " . escapeshellarg($nombre), $out);
    return !empty($out);
}

// === PETICIONES AJAX ===
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $resp = ['success' => false, 'message' => 'Acción desconocida'];

    switch ($_GET['action']) {
        case 'status':
            $resp = ['activo' => procesoActivo("A_MultiBot.exe")];
            break;
        case 'clear_log':
            $ok = limpiarArchivo(DISCORD_LOG_FILE);
            $resp = ['success' => $ok, 'message' => $ok ? '🧹 Log limpiado correctamente.' : '❌ No se pudo limpiar el log.'];
            break;
        case 'start_bot':
            $ok = ejecutarArchivo(DISCORD_EXECUTABLE);
            $resp = ['success' => $ok, 'message' => $ok ? '🚀 Bot iniciado correctamente.' : '❌ No se pudo iniciar el bot.'];
            break;
        case 'stop_bot':
            $ok = detenerProceso("A_MultiBot.exe");
            $resp = ['success' => $ok, 'message' => $ok ? '🛑 Bot detenido correctamente.' : '❌ No se encontró el proceso.'];
            break;
        case 'list_process':
            exec('wmic process where "name=\'A_MultiBot.exe\'" get Name,ProcessId,SessionName,SessionNumber,WorkingSetSize,ExecutablePath /format:list', $out);
            $procs = [];
            $current = [];
            foreach ($out as $line) {
                $line = trim($line);
                if ($line === '') {
                    if ($current) {
                        $current['mem'] = isset($current['WorkingSetSize']) ? round($current['WorkingSetSize']/1024/1024, 1) . ' MB' : 'N/A';
                        $procs[] = [
                            'name' => $current['Name'] ?? 'N/A',
                            'pid' => $current['ProcessId'] ?? 'N/A',
                            'session' => $current['SessionName'] ?? 'N/A',
                            'session_num' => $current['SessionNumber'] ?? 'N/A',
                            'mem' => $current['mem'],
                            'path' => $current['ExecutablePath'] ?? 'N/A'
                        ];
                        $current = [];
                    }
                    continue;
                }
                [$k, $v] = explode('=', $line, 2) + ['', ''];
                $current[$k] = $v;
            }
            echo json_encode($procs);
            exit;
    }
    echo json_encode($resp);
    exit;
}

// === ACCIONES MANUALES (Guardar archivos) ===
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_ini'])) {
        $mensaje = guardarArchivo(DISCORD_DEFAULT_MESSAGES, $_POST['contenido_ini'])
            ? "<div class='alert alert-success'>✅ Archivo default_messages.ini guardado correctamente.</div>"
            : "<div class='alert alert-danger'>❌ No se pudo guardar el archivo.</div>";
    }

    if (isset($_POST['guardar_json'])) {
        $mensaje = guardarArchivo(DISCORD_SETTINGS_JSON, $_POST['contenido_json'])
            ? "<div class='alert alert-success'>✅ Archivo settings.json guardado correctamente.</div>"
            : "<div class='alert alert-danger'>❌ No se pudo guardar el archivo.</div>";
    }
}

// === CHEQUEOS ===
$existe_ini  = file_exists(DISCORD_DEFAULT_MESSAGES);
$existe_json = file_exists(DISCORD_SETTINGS_JSON);
$existe_log  = file_exists(DISCORD_LOG_FILE);
$existe_exe  = file_exists(DISCORD_EXECUTABLE);
$activo      = procesoActivo("A_MultiBot.exe");

$ini_content  = $existe_ini ? leerArchivo(DISCORD_DEFAULT_MESSAGES) : "";
$json_content = $existe_json ? leerArchivo(DISCORD_SETTINGS_JSON) : "";
$log_content  = $existe_log ? leerArchivo(DISCORD_LOG_FILE) : "";
?>

<div class="container py-4 text-light">
  <h2 class="mb-4">🤖 Bot Discord - Configuración y Control</h2>
  <?= $mensaje ?>

  <div class="card bg-dark mb-4">
    <div class="card-body">
      <h5 class="card-title text-info d-flex justify-content-between align-items-center">
        ⚙️ Archivos y Estado
        <span id="estado-bot">
          <?= $activo ? '<span class="text-success fw-bold">🟢 Bot Activo</span>' : '<span class="text-danger fw-bold">🔴 Bot Detenido</span>' ?>
        </span>
      </h5>

      <table class="table table-dark table-bordered align-middle">
        <thead>
          <tr>
            <th>Archivo / Parámetro</th>
            <th>Ruta</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>A_Multibot</td>
            <td><?= DISCORD_MULTIBOT ? '✅ Activado' : '❌ Desactivado' ?></td>
            <td>-</td>
          </tr>
          <tr>
            <td>default_messages.ini</td>
            <td><?= DISCORD_DEFAULT_MESSAGES ?></td>
            <td>
              <button class="btn btn-primary btn-sm" onclick="mostrarEditor('ini')" <?= !$existe_ini ? 'disabled' : '' ?>>✏️ Editar</button>
            </td>
          </tr>
          <tr>
            <td>settings.json</td>
            <td><?= DISCORD_SETTINGS_JSON ?></td>
            <td>
              <button class="btn btn-primary btn-sm" onclick="mostrarEditor('json')" <?= !$existe_json ? 'disabled' : '' ?>>✏️ Editar</button>
            </td>
          </tr>
          <tr>
            <td>app.log</td>
            <td><?= DISCORD_LOG_FILE ?></td>
            <td>
              <button class="btn btn-info btn-sm" onclick="mostrarLog()" <?= !$existe_log ? 'disabled' : '' ?>>👁️ Ver</button>
              <button id="btnClearLog" class="btn btn-danger btn-sm" <?= !$existe_log ? 'disabled' : '' ?>>🧹 Vaciar</button>
            </td>
          </tr>
          <tr>
            <td>A_MultiBot.exe</td>
            <td><?= DISCORD_EXECUTABLE ?></td>
            <td>
              <button id="btnStartBot" class="btn btn-success btn-sm" <?= !$existe_exe || $activo ? 'disabled' : '' ?>>🚀 Iniciar</button>
              <button id="btnStopBot" class="btn btn-warning btn-sm" <?= !$activo ? 'disabled' : '' ?>>🛑 Detener</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- === TABLA DE PROCESOS === -->
  <div class="card bg-dark mb-4">
    <div class="card-body">
      <h5 class="card-title text-info d-flex justify-content-between align-items-center">
        ⚙️ Estado del Proceso A_MultiBot.exe
        <button id="btnCheckProcess" class="btn btn-outline-info btn-sm">🔍 Detectar Proceso</button>
      </h5>

      <table id="tablaProcesos" class="table table-dark table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>PID</th>
            <th>Sesión</th>
            <th># Sesión</th>
            <th>Uso Memoria</th>
            <th>Ruta Ejecutable</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="procesosBody">
          <tr><td colspan="7" class="text-center text-secondary">No se ha detectado ningún proceso.</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- EDITOR INI -->
  <div id="editor_ini" class="card bg-dark mb-4" style="display:none;">
    <div class="card-body">
      <h5 class="card-title text-info">📝 Editar Archivo: default_messages.ini</h5>
      <form method="POST">
        <textarea name="contenido_ini" class="form-control bg-dark text-light" rows="15"><?= $ini_content ?></textarea>
        <button type="submit" name="guardar_ini" class="btn btn-success mt-3">💾 Guardar Cambios</button>
        <button type="button" class="btn btn-secondary mt-3" onclick="cerrarEditor('ini')">❌ Cerrar</button>
      </form>
    </div>
  </div>

  <!-- EDITOR JSON -->
  <div id="editor_json" class="card bg-dark mb-4" style="display:none;">
    <div class="card-body">
      <h5 class="card-title text-info">📝 Editar Archivo: settings.json</h5>
      <form method="POST">
        <textarea name="contenido_json" class="form-control bg-dark text-light" rows="15"><?= $json_content ?></textarea>
        <button type="submit" name="guardar_json" class="btn btn-success mt-3">💾 Guardar Cambios</button>
        <button type="button" class="btn btn-secondary mt-3" onclick="cerrarEditor('json')">❌ Cerrar</button>
      </form>
    </div>
  </div>

  <!-- VER LOG -->
  <div id="ver_log" class="card bg-dark mb-4" style="display:none;">
    <div class="card-body">
      <h5 class="card-title text-info">📜 Ver Archivo: app.log</h5>
      <pre id="log-content" class="bg-black text-white p-3 rounded" style="height:400px; overflow-y:auto;"><?= $log_content ?></pre>
      <button type="button" class="btn btn-secondary mt-3" onclick="cerrarLog()">❌ Cerrar</button>
    </div>
  </div>
</div>

<!-- TOAST CONTAINER -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
  <div id="toastContainer"></div>
</div>

<script>
// --- UI helpers ---
function mostrarEditor(tipo){ document.getElementById('editor_'+tipo).style.display='block'; }
function cerrarEditor(tipo){ document.getElementById('editor_'+tipo).style.display='none'; }
function mostrarLog(){ document.getElementById('ver_log').style.display='block'; }
function cerrarLog(){ document.getElementById('ver_log').style.display='none'; }

// --- Toasts modernos ---
function showToast(msg, success=true) {
  const toastId = 'toast' + Date.now();
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-white ${success ? 'bg-success' : 'bg-danger'} border-0 show mb-2`;
  toast.id = toastId;
  toast.role = 'alert';
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${msg}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  document.getElementById('toastContainer').appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
}

// --- Acciones AJAX ---
function accion(url, successMsg=null, callback=null) {
  fetch(url)
    .then(r => r.json())
    .then(data => {
      showToast(data.message || successMsg || 'Comando ejecutado', data.success);
      if (callback) callback(data);
    })
    .catch(() => showToast("❌ Error de conexión", false));
}

// --- Acciones principales ---
document.getElementById('btnClearLog')?.addEventListener('click', ()=>accion('pages/bot_discord.php?action=clear_log'));

document.getElementById('btnStartBot')?.addEventListener('click', ()=>{
  showToast('⏳ Iniciando A_MultiBot.exe...', true);
  accion('pages/bot_discord.php?action=start_bot', null, ()=>{
    setTimeout(()=>{
      fetch('pages/bot_discord.php?action=status')
        .then(r=>r.json())
        .then(data=>{
          actualizarEstado(data.activo);
          if (data.activo) showToast('🟢 Proceso detectado correctamente.', true);
          else showToast('❌ No se detectó el proceso después de 5 segundos.', false);
        });
    },5000);
  });
});

document.getElementById('btnStopBot')?.addEventListener('click', ()=>accion('pages/bot_discord.php?action=stop_bot', null, ()=>actualizarEstado(false)));

function actualizarEstado(activo){
  const est = document.getElementById('estado-bot');
  if (activo) est.innerHTML = '<span class="text-success fw-bold">🟢 Bot Activo</span>';
  else est.innerHTML = '<span class="text-danger fw-bold">🔴 Bot Detenido</span>';
}

// --- Tabla de procesos ---
document.getElementById('btnCheckProcess')?.addEventListener('click', ()=>{
  fetch('pages/bot_discord.php?action=list_process')
    .then(r=>r.json())
    .then(data=>{
      const body=document.getElementById('procesosBody');
      body.innerHTML='';
      if(!data.length){
        body.innerHTML='<tr><td colspan="7" class="text-center text-danger">No hay procesos A_MultiBot.exe en ejecución.</td></tr>';
        actualizarEstado(false);
        return;
      }
      actualizarEstado(true);
      data.forEach(p=>{
        body.innerHTML+=`
          <tr>
            <td>${p.name}</td>
            <td>${p.pid}</td>
            <td>${p.session}</td>
            <td>${p.session_num}</td>
            <td>${p.mem}</td>
            <td>${p.path}</td>
            <td><button class="btn btn-warning btn-sm" onclick="stopProcess()">🛑 Detener</button></td>
          </tr>`;
      });
    })
    .catch(()=>showToast('❌ Error al obtener los procesos',false));
});

function stopProcess(){
  accion('pages/bot_discord.php?action=stop_bot',null,()=>{
    document.getElementById('btnCheckProcess').click();
  });
}

// --- Estado del bot en tiempo real ---
setInterval(()=>{
  fetch('pages/bot_discord.php?action=status')
    .then(r=>r.json())
    .then(d=>actualizarEstado(d.activo));
},4000);
</script>
