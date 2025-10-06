# ⚔️ Panel de Administración - Warcraft III

**Versión:** 1.0  
**Autor:** [Azzlaer](https://github.com/Azzlaer)  
**Asistencia Técnica:** ChatGPT (OpenAI GPT-5)  
**Repositorio:** [github.com/Azzlaer/Panel_Warcraft3](https://github.com/Azzlaer/Panel_Warcraft3)

---

## 🧩 Descripción del Proyecto

Este panel fue diseñado para gestionar servidores de **Warcraft III** con el sistema **GHost++**, permitiendo una administración avanzada desde una interfaz web moderna.  
Está desarrollado en **PHP**, **Bootstrap**, **jQuery** y con integración total con **Python** para monitoreo y control de procesos del servidor.

---

## 🧠 Funcionalidades Principales

- **Gestión de Bots:** administración de `bots.xml` (crear, editar, eliminar).
- **Monitor de Python:** control remoto de `ghost_monitor.py` (inicio, detención, estado).
- **Procesos Python:** listado completo de procesos Python activos (PID, sesión, memoria, ruta).
- **Actualización de PIP:** actualización remota de módulos de Python.
- **Subida de Mapas:** carga de archivos `.w3x` en `C:\Games\Warcraft III\Maps\Download\`.
- **Subida de Archivos CFG:** para configuraciones de mapas en `C:\Servidores\wc3bots\mapcfgs\`.
- **Edición de Archivos:** modificación directa de:
  - `settings.json`
  - `default_messages.ini`
  - `motd.txt`, `gameover.txt`, `gameloaded.txt`
- **Idiomas del Juego:** visualización de archivos de idioma:
  - `language_spanish.cfg`
  - `language_german.cfg`
  - `language_russian.cfg`
  - `language_turkish.cfg`
- **Sistema de Baneo (MySQL):** ban, desban y registro de usuarios con motivo y duración.
- **Gestión de IPs Baneadas:** edición del archivo `ipblacklist.txt`.
- **Listador de Procesos Activos:** lectura y estado de procesos definidos en `bots.xml`.
- **Soporte y Créditos:** sección informativa del proyecto con enlaces y detalles técnicos.

---

## ⚙️ Rutas Importantes

```text
C:\Servidores\wc3bots\bots.xml
C:\Servidores\wc3bots\mapcfgs\
C:\Servidores\wc3bots\motd.txt
C:\Servidores\wc3bots\gameover.txt
C:\Servidores\wc3bots\gameloaded.txt
C:\Servidores\wc3bots\ipblacklist.txt
C:\Games\Warcraft III\Maps\Download\
C:\Users\Guardia\AppData\Local\Programs\Python\Python312\python.exe
```

---

## 🧰 Tecnologías Utilizadas

- PHP 8.2  
- Bootstrap 5.3  
- jQuery 3.7  
- Python 3.12  
- MySQL  
- XAMPP  
- Windows CMD / WMIC

---

## 👨‍💻 Créditos

| Rol | Nombre |
|-----|--------|
| Autor Principal | **Azzlaer** |
| Asistente Técnico | **ChatGPT (OpenAI GPT-5)** |
| Proyecto | [Panel_Warcraft3](https://github.com/Azzlaer/Panel_Warcraft3) |

---

## 📜 Licencia

Este proyecto está bajo la licencia **MIT**.  
Puedes modificarlo, distribuirlo y usarlo libremente, siempre manteniendo los créditos al autor original.

---

**© 2025 - Proyecto desarrollado por Azzlaer con soporte de ChatGPT (OpenAI GPT-5).**
