<?php
require_once __DIR__ . '/../config.php';

// === Conexión a la base de datos ===
$servername = "localhost";
$username = "latinbattle";
$password = "Z)/yQ(RnhY!1!_N.";
$dbname = "latinbat_chile";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => "❌ Conexión fallida: " . $conn->connect_error]));
}

// === AJAX: Desbanear ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && isset($_POST['unban'])) {
    header('Content-Type: application/json');
    $ban_id = intval($_POST['unban']);
    $sql = "DELETE FROM bans WHERE id = $ban_id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// === Insertar nuevo ban ===
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ban'])) {
    $name = $conn->real_escape_string($_POST["name"]);
    $admin = $conn->real_escape_string($_POST["admin"]);
    $reason = $conn->real_escape_string($_POST["reason"]);
    $ip = $conn->real_escape_string($_POST["ip"]);
    $date = date("Y-m-d H:i:s");

    $expire = NULL;
    if (!empty($_POST["expire"])) {
        $expire = date("Y-m-d H:i:s", strtotime("+" . intval($_POST["expire"]) . " days"));
    }

    $sql = "INSERT INTO bans (name, ip, admin, reason, date, expiredate) 
            VALUES ('$name', '$ip', '$admin', '$reason', '$date', " . ($expire ? "'$expire'" : "NULL") . ")";

    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert alert-success'>✅ Usuario <b>$name</b> ha sido baneado correctamente.</div>";
    } else {
        $message = "<div class='alert alert-danger'>❌ Error al banear: {$conn->error}</div>";
    }
}

// === Obtener lista de bans ===
$ban_list = [];
$result = $conn->query("SELECT * FROM bans ORDER BY date DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ban_list[] = $row;
    }
}
$conn->close();
?>

<div class="container py-4 text-light">
  <h2 class="mb-3">🚫 Sistema de Baneo</h2>
  <?= $message ?>

  <!-- Formulario de Ban -->
  <form method="POST" class="p-3 rounded bg-dark border border-secondary mb-4">
    <div class="row g-2">
      <div class="col-md-6">
        <input type="text" name="name" class="form-control bg-secondary text-white border-0" placeholder="👤 Nombre del jugador" required>
      </div>
      <div class="col-md-6">
        <input type="text" name="admin" class="form-control bg-secondary text-white border-0" placeholder="🛡️ Admin que banea" required>
      </div>
      <div class="col-md-6">
        <input type="text" name="ip" class="form-control bg-secondary text-white border-0" placeholder="🌐 IP del jugador (opcional)">
      </div>
      <div class="col-md-6">
        <input type="number" name="expire" class="form-control bg-secondary text-white border-0" placeholder="⏳ Días de duración (opcional)">
      </div>
      <div class="col-12">
        <textarea name="reason" class="form-control bg-secondary text-white border-0" placeholder="✍️ Razón del ban" required></textarea>
      </div>
      <div class="col-12 text-center mt-3">
        <button type="submit" name="ban" class="btn btn-danger px-4">💥 Banear Usuario</button>
      </div>
    </div>
  </form>

  <!-- Lista de Baneados -->
  <h4 class="mb-3">📝 Lista de Baneados</h4>
  <?php if (empty($ban_list)): ?>
    <div class="alert alert-info text-center">No hay usuarios baneados.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-dark table-bordered align-middle">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>IP</th>
            <th>Razón</th>
            <th>Admin</th>
            <th>Fecha</th>
            <th>Expiración</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ban_list as $ban): ?>
          <tr>
            <td><?= htmlspecialchars($ban['name']) ?></td>
            <td><?= htmlspecialchars($ban['ip']) ?></td>
            <td><?= htmlspecialchars($ban['reason']) ?></td>
            <td><?= htmlspecialchars($ban['admin']) ?></td>
            <td><?= date("d-m-Y H:i", strtotime($ban['date'])) ?></td>
            <td>
              <?= (empty($ban['expiredate']) || $ban['expiredate'] == '0000-00-00 00:00:00') 
                  ? 'No expira' 
                  : date("d-m-Y H:i", strtotime($ban['expiredate'])) ?>
            </td>
            <td>
              <button class="btn btn-warning btn-sm btn-unban" data-id="<?= intval($ban['id']) ?>">❌ Desbanear</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
$(function(){
  $('.btn-unban').click(function(){
    const id = $(this).data('id');
    if(!confirm('¿Seguro que quieres desbanear este usuario?')) return;

    $.post('pages/ver_bans.php', { ajax: 1, unban: id }, function(r){
      if(r.success){
        alert('✅ Usuario desbaneado correctamente');
        $('.sidebar .nav-link.active').click(); // recarga la sección actual
      } else {
        alert('❌ Error: ' + (r.error || 'No se pudo procesar'));
      }
    }, 'json');
  });
});
</script>
