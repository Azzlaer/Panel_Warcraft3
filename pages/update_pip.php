<?php
require_once __DIR__ . '/../config.php';
$pyBin = defined('PYTHON_BIN') ? PYTHON_BIN : 'python';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=utf-8');
    @ob_end_flush();
    @ob_implicit_flush(true);

    // Comando para ensurepip y upgrade pip
    $cmd = escapeshellarg($pyBin) . ' -m ensurepip && ' .
           escapeshellarg($pyBin) . ' -m pip install --upgrade pip';

    $descriptorspec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $proc = proc_open($cmd, $descriptorspec, $pipes);
    if (is_resource($proc)) {
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            $out = fgets($pipes[1]);
            $err = fgets($pipes[2]);
            if ($out !== false) {
                echo $out; flush();
            }
            if ($err !== false) {
                echo $err; flush();
            }
        }
        proc_close($proc);
    } else {
        echo "No se pudo ejecutar el comando.\n";
    }
    exit;
}
?>
<div class="container py-4 text-light">
  <h2 class="mb-3">Actualizar pip</h2>
  <p class="text-white">Este proceso ejecutará:
    <code>python -m ensurepip</code> y
    <code>python -m pip install --upgrade pip</code>
  </p>
  <button id="btnUpdate" class="btn btn-primary mb-3">Actualizar ahora</button>
  <pre id="output" style="background:#000;color:#0f0;padding:1em;height:300px;overflow:auto;"></pre>
</div>

<script>
document.getElementById('btnUpdate').addEventListener('click', function(){
  const out = document.getElementById('output');
  out.textContent = 'Iniciando actualización...\n';
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'pages/update_pip.php', true);
  xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 3) { // datos parciales en tiempo real
      out.textContent += xhr.responseText;
      out.scrollTop = out.scrollHeight;
    }
    if (xhr.readyState === 4) {
      out.textContent += "\nProceso finalizado.\n";
    }
  };
  xhr.send('start=1');
});
</script>
