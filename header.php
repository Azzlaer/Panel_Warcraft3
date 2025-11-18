<?php
require_once "config.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Warcraft 3 Bots</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #121212; color: #eee; }
        .sidebar { min-height: 100vh; background: #1e1e1e; }
        .nav-link { color: #bbb; }
        .nav-link.active { background: #0d6efd; color: #fff; }
        main { padding: 20px; }
    </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3">
      <h3 class="text-light mb-4">⚔️ Warcraft 3</h3>
      <div class="nav flex-column nav-pills">
        <a href="#" class="nav-link" data-section="pages/add_bot">➕ Agregar Bot</a>
        <a href="#" class="nav-link" data-section="pages/add_log">➕ Agregar Log</a>	  
        <a href="#" class="nav-link" data-section="pages/bot_manager">🤖 Bot Manager</a>
		<a href="#" class="nav-link" data-section="pages/listar_procesos">🤖 Bot Process</a>
		<a href="#" class="nav-link" data-section="pages/bot_discord">🤖 Bot Discord</a>
		<a href="#" class="nav-link" data-section="pages/motd_manager">📜 MOTD Manager</a>
		<a href="#" class="nav-link" data-section="pages/ip_blacklist">❌ IP Bans</a>
		<a href="#" class="nav-link" data-section="pages/ver_bans">🚫 Ver Bans</a>

        <a href="#" class="nav-link" data-section="pages/logs">📜 Logs</a>

        <a href="#" class="nav-link" data-section="pages/python_monitor">🐍 Python Monitor</a>
        <a href="#" class="nav-link" data-section="pages/update_pip">🐍 Python Update</a>
        <a href="#" class="nav-link" data-section="pages/python_processes">🐍 Procesos Python</a>

        <a href="#" class="nav-link" data-section="pages/edit_settings">🧩 Editar settings.json</a>
        <a href="#" class="nav-link" data-section="pages/edit_messages">✉️ Editar mensajes (INI)</a>

        <a href="#" class="nav-link" data-section="pages/upload_map">🗺️ Sube tu mapa</a>
        <a href="#" class="nav-link" data-section="pages/upload_cfg">⚙️ Sube archivo CFG</a>
        <a href="#" class="nav-link" data-section="pages/list_maps">📂 Mapas Subidos</a>
		
		<a href="#" class="nav-link" data-section="pages/language_viewer">🌐 Archivo de Lenguaje</a>

		<a href="#" class="nav-link" data-section="pages/soporte">📎 Soporte</a>
        <a href="logout.php" class="nav-link text-danger">🚪 Cerrar Sesión</a>
      </div>
    </nav>

    <!-- Main content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="main">
      <div class="text-center p-5 text-light">
        👋 Bienvenido al Panel de demostracion
      </div>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

      <script>
      $(function(){
        $('.sidebar .nav-link').on('click', function(e){
          const page = $(this).data('section') || $(this).data('page');
          if (!page) return; // enlaces normales como logout

          e.preventDefault();
          $('.sidebar .nav-link').removeClass('active');
          $(this).addClass('active');
          $('#main').html('<div class="p-5 text-center">Cargando…</div>');
          const path = page.startsWith('pages/') ? page : 'pages/' + page;
          $('#main').load(path + '.php');
        });
      });
      </script>
    </main>
  </div>
</div>
</body>
</html>
