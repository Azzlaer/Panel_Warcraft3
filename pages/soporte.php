<?php
require_once __DIR__ . '/../config.php';
?>
<div class="container py-5 text-light">
  <div class="text-center mb-4">
    <h1 class="mb-3">🧩 Panel de Administración - Warcraft III</h1>
    <h5 class="text-secondary">Versión 1.0 - Proyecto de Gestión y Monitoreo</h5>
    <p>
      Desarrollado por <strong>Azzlaer</strong> junto a la asistencia técnica de <strong>ChatGPT (OpenAI)</strong><br>
      Repositorio oficial del proyecto:  
      <a href="https://github.com/Azzlaer/Panel_Warcraft3" target="_blank" class="link-info fw-bold">
        github.com/Azzlaer/Panel_Warcraft3
      </a>
    </p>
  </div>

  <hr class="border-secondary mb-5">

  <h3 class="mb-3">📘 Descripción General del Proyecto</h3>
  <p class="lead">
    Este panel fue diseñado para gestionar de manera completa servidores <b>Warcraft III</b> utilizando el sistema de bots GHost++ y herramientas
    Python integradas. Permite administrar configuraciones, procesos, mapas, bans, archivos de idioma y más, todo desde una interfaz moderna
    construida con <b>PHP 8</b>, <b>Bootstrap 5</b> y <b>jQuery</b>.
  </p>

  <h4 class="mt-5 mb-3 text-primary">🧠 Funcionalidades Principales</h4>

  <ul class="fs-5">
    <li><b>Gestión de Bots:</b> Crear, editar y eliminar bots configurados en <code>bots.xml</code>.</li>
    <li><b>Agregar y Editar Logs:</b> Integración con archivos de registro del servidor para análisis en tiempo real.</li>
    <li><b>Monitor de Python:</b> Control remoto de <code>ghost_monitor.py</code> (inicio, detención, estado en vivo).</li>
    <li><b>Procesos Python:</b> Listado detallado de procesos Python activos con PID, memoria y ruta de ejecución.</li>
    <li><b>Actualizador PIP:</b> Interfaz que permite actualizar el gestor <code>pip</code> directamente desde el panel.</li>
    <li><b>Subida de Mapas:</b> Carga y almacenamiento de archivos <code>.w3x</code> en la carpeta configurada de Warcraft III.</li>
    <li><b>Gestión de Archivos CFG:</b> Subida y manejo de configuraciones <code>.cfg</code> para los mapas.</li>
    <li><b>Editor de Archivos:</b> Modificación directa de:
      <ul>
        <li><code>settings.json</code></li>
        <li><code>default_messages.ini</code></li>
        <li><code>motd.txt</code>, <code>gameover.txt</code>, <code>gameloaded.txt</code></li>
      </ul>
    </li>
    <li><b>Editor de Idiomas:</b> Visualización de archivos de lenguaje como:
      <ul>
        <li><code>language_spanish.cfg</code></li>
        <li><code>language_german.cfg</code></li>
        <li><code>language_russian.cfg</code></li>
        <li><code>language_turkish.cfg</code></li>
      </ul>
    </li>
    <li><b>Gestión de IPs Baneadas:</b> Control completo de <code>ipblacklist.txt</code> con opciones de agregar, editar y eliminar líneas.</li>
    <li><b>Gestión de Baneos en Base de Datos:</b> Integración con MySQL para registrar, visualizar y eliminar bans de usuarios.</li>
    <li><b>Listador de Procesos Activos:</b> Detección de procesos definidos en <code>bots.xml</code> con estado en tiempo real y detalles del sistema.</li>
  </ul>

  <h4 class="mt-5 mb-3 text-primary">⚙️ Archivos Principales y Rutas Configurables</h4>
  <pre class="bg-dark text-white p-3 rounded">
C:\Servidores\wc3bots\bots.xml
C:\Servidores\wc3bots\mapcfgs\
C:\Servidores\wc3bots\motd.txt
C:\Servidores\wc3bots\gameover.txt
C:\Servidores\wc3bots\gameloaded.txt
C:\Servidores\wc3bots\ipblacklist.txt
C:\Games\Warcraft III\Maps\Download\
C:\Users\Guardia\AppData\Local\Programs\Python\Python312\python.exe
  </pre>

  <h4 class="mt-5 mb-3 text-primary">🧰 Tecnologías Utilizadas</h4>
  <ul class="fs-5">
    <li>PHP 8.2 con XAMPP</li>
    <li>HTML5 + Bootstrap 5</li>
    <li>jQuery 3.7</li>
    <li>Python 3.12 (integrado con ejecución remota)</li>
    <li>MySQL para sistema de bans</li>
    <li>Windows CMD y WMIC para gestión de procesos</li>
  </ul>

  <h4 class="mt-5 mb-3 text-primary">👨‍💻 Créditos</h4>
  <div class="border rounded-3 p-3 bg-dark">
    <p><b>Desarrollo principal:</b> <span class="text-info">Azzlaer</span></p>
    <p><b>Asistencia técnica e integración:</b> ChatGPT (OpenAI GPT-5)</p>
    <p><b>Idea y dirección:</b> Azzlaer</p>
    <p><b>Proyecto público:</b> 
      <a href="https://github.com/Azzlaer/Panel_Warcraft3" target="_blank" class="link-info">Panel_Warcraft3</a>
    </p>
  </div>

  <h4 class="mt-5 mb-3 text-primary">📄 Licencia</h4>
  <p>
    Este proyecto se distribuye de forma abierta bajo licencia <b>MIT</b>.  
    Puedes usarlo, modificarlo y compartirlo libremente, dando crédito al autor original.
  </p>

  <div class="text-center mt-5">
    <a href="https://github.com/Azzlaer/Panel_Warcraft3" target="_blank" class="btn btn-outline-info btn-lg">
      🌐 Visitar Proyecto en GitHub
    </a>
  </div>
</div>
