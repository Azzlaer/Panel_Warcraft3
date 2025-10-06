<?php
// pages/ip_blacklist.php
require_once __DIR__ . '/../config.php';

// Seguridad: iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta al archivo (fallback a la ruta que indicaste)
$blacklistFile = defined('IP_BLACKLIST_FILE')
    ? IP_BLACKLIST_FILE
    : (defined('WC3_BOTS_PATH')
        ? rtrim(WC3_BOTS_PATH, '\\/') . DIRECTORY_SEPARATOR . 'ipblacklist.txt'
        : 'C:\\Servidores\\wc3bots\\ipblacklist.txt');

// Ensure directory exists (no crea archivo todavía)
$dir = dirname($blacklistFile);
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

/* ---------- Helpers ---------- */
function read_blacklist($path) {
    $lines = [];
    if (!file_exists($path)) return $lines;
    $contents = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($contents === false) return $lines;
    foreach ($contents as $ln) {
        $ln = trim($ln);
        if ($ln === '') continue;
        $lines[] = $ln;
    }
    return $lines;
}

function save_blacklist($path, array $lines) {
    // Normalizar: eliminar líneas vacías y duplicados, mantener orden
    $lines = array_values(array_filter(array_map('trim', $lines), fn($v)=>$v !== ''));
    // Write with exclusive lock
    $tmp = $path . '.tmp';
    $fp = @fopen($tmp, 'wb');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }
    foreach ($lines as $l) {
        fwrite($fp, $l . PHP_EOL);
    }
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    // atomic rename
    return rename($tmp, $path);
}

function is_valid_ip_or_cidr($val) {
    $val = trim($val);
    if ($val === '') return false;
    // CIDR IPv4 like 1.2.3.0/24
    if (strpos($val, '/') !== false) {
        [$ip, $mask] = explode('/', $val, 2) + [1 => null];
        if (!is_numeric($mask) || $mask < 0 || $mask > 128) return false;
        // test ip
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ||
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return true;
        }
        return false;
    }
    // plain IP v4 or v6
    return filter_var($val, FILTER_VALIDATE_IP) !== false;
}

/* ---------- AJAX API ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    // Leer lista actual
    $list = read_blacklist($blacklistFile);

    if ($action === 'list') {
        echo json_encode(['ok'=>true,'data'=>$list]);
        exit;
    }

    if ($action === 'add') {
        $ip = trim($_POST['ip'] ?? '');
        if ($ip === '') { echo json_encode(['ok'=>false,'error'=>'IP vacía']); exit; }
        if (!is_valid_ip_or_cidr($ip)) { echo json_encode(['ok'=>false,'error'=>'Formato de IP/CIDR inválido']); exit; }
        if (in_array($ip, $list, true)) { echo json_encode(['ok'=>false,'error'=>'IP ya existe']); exit; }
        $list[] = $ip;
        if (save_blacklist($blacklistFile, $list)) {
            echo json_encode(['ok'=>true,'msg'=>'IP agregada','data'=>$list]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No se pudo guardar el archivo (permisos?)']);
        }
        exit;
    }

    if ($action === 'edit') {
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        $ip = trim($_POST['ip'] ?? '');
        if ($index < 0 || $index >= count($list)) { echo json_encode(['ok'=>false,'error'=>'Índice inválido']); exit; }
        if ($ip === '') { echo json_encode(['ok'=>false,'error'=>'IP vacía']); exit; }
        if (!is_valid_ip_or_cidr($ip)) { echo json_encode(['ok'=>false,'error'=>'Formato de IP/CIDR inválido']); exit; }
        // Si el nuevo valor ya existe en otra línea, impedir duplicado
        foreach ($list as $i => $v) {
            if ($i !== $index && strcasecmp($v, $ip) === 0) {
                echo json_encode(['ok'=>false,'error'=>'La IP ya existe en otra línea']); exit;
            }
        }
        $list[$index] = $ip;
        if (save_blacklist($blacklistFile, $list)) {
            echo json_encode(['ok'=>true,'msg'=>'IP modificada','data'=>$list]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No se pudo guardar el archivo']);
        }
        exit;
    }

    if ($action === 'delete') {
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        if ($index < 0 || $index >= count($list)) { echo json_encode(['ok'=>false,'error'=>'Índice inválido']); exit; }
        array_splice($list, $index, 1);
        if (save_blacklist($blacklistFile, $list)) {
            echo json_encode(['ok'=>true,'msg'=>'IP eliminada','data'=>$list]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No se pudo guardar el archivo']);
        }
        exit;
    }

    if ($action === 'clear') {
        if (save_blacklist($blacklistFile, [])) {
            echo json_encode(['ok'=>true,'msg'=>'Lista vaciada','data'=>[]]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No se pudo vaciar el archivo']);
        }
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Acción no soportada']);
    exit;
}

/* ---------- Página HTML ---------- */
$list = read_blacklist($blacklistFile);
?>
<div class="container py-4 text-light">
  <h2 class="mb-3">🛡️ IP Blacklist</h2>

  <p class="mb-3">
    Añade las direcciones IP (o CIDR) que quieres bloquear. Ejemplos válidos:
    <code>192.168.1.100</code>, <code>2001:db8::1</code>, <code>1.2.3.0/24</code>.
  </p>

  <div id="msgBox" class="alert d-none"></div>

  <div class="mb-3 row g-2">
    <div class="col-md-8">
      <input id="ipInput" class="form-control bg-secondary text-light" placeholder="IP o CIDR (ej: 1.2.3.4 o 1.2.3.0/24)">
    </div>
    <div class="col-md-4">
      <button id="btnAdd" class="btn btn-primary w-100">Agregar IP</button>
    </div>
  </div>

  <div class="mb-3">
    <button id="btnClear" class="btn btn-danger">Vaciar lista</button>
  </div>

  <div class="table-responsive">
    <table id="tblBlacklist" class="table table-dark table-striped align-middle">
      <thead>
        <tr>
          <th style="width:60px;">#</th>
          <th>IP / CIDR</th>
          <th style="width:200px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($list)): ?>
          <tr><td colspan="3" class="text-center text-muted">La lista está vacía</td></tr>
        <?php else: foreach ($list as $i => $ip): ?>
          <tr data-index="<?= $i ?>">
            <td><?= $i+1 ?></td>
            <td class="ipVal"><?= htmlspecialchars($ip) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-light btn-edit">Editar</button>
              <button class="btn btn-sm btn-outline-danger btn-delete">Eliminar</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content bg-dark text-light">
        <div class="modal-header">
          <h5 class="modal-title">Editar IP</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input id="editIpInput" class="form-control bg-secondary text-light">
          <input type="hidden" id="editIndex">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" id="saveEdit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showMsg(text, type='info') {
  const box = $('#msgBox');
  box.removeClass('d-none alert-info alert-success alert-danger alert-warning')
     .addClass('alert alert-' + (type==='error'?'danger':type))
     .text(text);
  setTimeout(()=> box.addClass('d-none'), 5000);
}

function rebuildTable(list) {
  const tbody = $('#tblBlacklist tbody');
  tbody.empty();
  if (!Array.isArray(list) || list.length === 0) {
    tbody.append('<tr><td colspan="3" class="text-center text-muted">La lista está vacía</td></tr>');
    return;
  }
  list.forEach((ip, idx) => {
    const row = $('<tr>').attr('data-index', idx);
    row.append($('<td>').text(idx+1));
    row.append($('<td>').addClass('ipVal').text(ip));
    const actions = $('<td>');
    actions.append($('<button>').addClass('btn btn-sm btn-outline-light btn-edit me-1').text('Editar'));
    actions.append($('<button>').addClass('btn btn-sm btn-outline-danger btn-delete').text('Eliminar'));
    row.append(actions);
    tbody.append(row);
  });
}

$(function(){
  // Agregar IP
  $('#btnAdd').click(function(){
    const ip = $('#ipInput').val().trim();
    if (!ip) { showMsg('Introduce una IP o CIDR','warning'); return; }
    $.post('pages/ip_blacklist.php', { action: 'add', ip }, function(res){
      if (res.ok) {
        rebuildTable(res.data);
        $('#ipInput').val('');
        showMsg(res.msg || 'IP agregada','success');
      } else showMsg(res.error || 'Error','error');
    }, 'json').fail(()=> showMsg('Error al conectar','error'));
  });

  // Edit (abrir modal)
  $(document).on('click', '.btn-edit', function(){
    const tr = $(this).closest('tr');
    const idx = parseInt(tr.data('index'));
    const ip = tr.find('.ipVal').text().trim();
    $('#editIndex').val(idx);
    $('#editIpInput').val(ip);
    const mod = new bootstrap.Modal(document.getElementById('editModal'));
    mod.show();
  });

  // Guardar edición
  $('#saveEdit').click(function(){
    const idx = parseInt($('#editIndex').val());
    const ip = $('#editIpInput').val().trim();
    if (!ip) { showMsg('Introduce una IP o CIDR','warning'); return; }
    $.post('pages/ip_blacklist.php', { action: 'edit', index: idx, ip }, function(res){
      if (res.ok) {
        rebuildTable(res.data);
        showMsg(res.msg || 'IP modificada','success');
        bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
      } else showMsg(res.error || 'Error','error');
    }, 'json').fail(()=> showMsg('Error al conectar','error'));
  });

  // Eliminar
  $(document).on('click', '.btn-delete', function(){
    if (!confirm('Eliminar esta IP de la blacklist?')) return;
    const tr = $(this).closest('tr');
    const idx = parseInt(tr.data('index'));
    $.post('pages/ip_blacklist.php', { action: 'delete', index: idx }, function(res){
      if (res.ok) {
        rebuildTable(res.data);
        showMsg(res.msg || 'IP eliminada','success');
      } else showMsg(res.error || 'Error','error');
    }, 'json').fail(()=> showMsg('Error al conectar','error'));
  });

  // Vaciar lista
  $('#btnClear').click(function(){
    if (!confirm('Vaciar toda la blacklist?')) return;
    $.post('pages/ip_blacklist.php', { action: 'clear' }, function(res){
      if (res.ok) {
        rebuildTable(res.data);
        showMsg(res.msg || 'Lista vaciada','success');
      } else showMsg(res.error || 'Error','error');
    }, 'json').fail(()=> showMsg('Error al conectar','error'));
  });
});
</script>
