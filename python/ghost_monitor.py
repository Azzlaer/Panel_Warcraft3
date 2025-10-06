import os, time, re, json, configparser, logging, requests, threading, sys
from typing import List, Dict

APP_NAME = "GhostMonitorLOG"
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # raíz del panel
CONFIG_INI_PATH  = os.path.join(BASE_DIR, "config", "default_messages.ini")
CONFIG_JSON_PATH = os.path.join(BASE_DIR, "data", "settings.json")
APP_LOG_PATH     = os.path.join(BASE_DIR, "python", "ghost_monitor.log")

# === Logging ===
os.makedirs(os.path.dirname(APP_LOG_PATH), exist_ok=True)
logging.basicConfig(
    filename=APP_LOG_PATH,
    filemode="a",
    format="%(asctime)s [%(levelname)s] %(message)s",
    level=logging.INFO
)
logging.info("=== GhostMonitor iniciado ===")

class ConfigWatcher:
    def __init__(self, filepath: str):
        self.filepath = filepath
        self.last_mtime = os.path.getmtime(filepath) if os.path.exists(filepath) else 0.0
        self.config = self._load()

    def _load(self) -> configparser.ConfigParser:
        cfg = configparser.ConfigParser()
        if os.path.exists(self.filepath):
            cfg.read(self.filepath, encoding="utf-8")
        if "MESSAGES" not in cfg:
            cfg["MESSAGES"] = {}
        return cfg

    def refresh_if_changed(self) -> None:
        if os.path.exists(self.filepath):
            m = os.path.getmtime(self.filepath)
            if m != self.last_mtime:
                self.last_mtime = m
                self.config = self._load()
                logging.info("%s: default_messages.ini recargado", APP_NAME)

def send_webhook(url: str, msg: str):
    if not url:
        logging.warning("Webhook vacío, mensaje: %s", msg)
        return
    try:
        r = requests.post(url, json={"content": msg}, timeout=8)
        if r.status_code not in (200, 201, 204):
            logging.error("Webhook error %s: %s", r.status_code, r.text[:200])
    except Exception as e:
        logging.error("Webhook exception: %s", e)

def tail_file(path: str):
    """Generador tipo 'tail -f'."""
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        f.seek(0, os.SEEK_END)
        while True:
            line = f.readline()
            if not line:
                time.sleep(0.5)
                continue
            yield line.rstrip("\r\n")

def process_line(line: str, cfg: configparser.ConfigParser, webhook: str):
    try:
        patterns = [
            (r"creating game \[(.*)\]",        "messagecreate",   "Game created: {game_name}",  lambda m,t: t.replace("{game_name}", m.group(1))),
            (r"player \[(.*)\|(.+?)\] joined", "messageplayer",   "{user} connected from {ip}", lambda m,t: t.replace("{user}", m.group(1)).replace("{ip}", m.group(2))),
            (r"deleting player \[(.*)\]:",     "messagetoleave",  "{user} left the game",      lambda m,t: t.replace("{user}", m.group(1))),
            (r"connecting to server \[(.*?)\]","messagetoconnect","Connected to server {SERVIDOR}", lambda m,t: t.replace("{SERVIDOR}", m.group(1))),
            (r"\[Lobby\] (.+)",                None,              None,                         lambda m,t: m.group(0))
        ]
        for pat,key,default,fmt in patterns:
            m = re.search(pat, line, re.IGNORECASE)
            if m:
                tpl = cfg["MESSAGES"].get(key, default) if key else None
                send_webhook(webhook, fmt(m, tpl if tpl else ""))
                return
    except Exception as e:
        logging.error("process_line error: %s en %r", e, line)

def worker(log_path: str, webhook: str, cfg_watch: ConfigWatcher, stop_evt: threading.Event):
    if not os.path.exists(log_path):
        # crea archivo vacío para no abortar
        os.makedirs(os.path.dirname(log_path), exist_ok=True)
        open(log_path, 'a').close()
        logging.warning("Archivo de log no existía, creado vacío: %s", log_path)

    logging.info("Monitor iniciado para %s", log_path)
    for line in tail_file(log_path):
        if stop_evt.is_set():
            break
        cfg_watch.refresh_if_changed()
        process_line(line, cfg_watch.config, webhook)
    logging.info("Monitor detenido para %s", log_path)

def load_settings() -> List[Dict[str, str]]:
    if not os.path.exists(CONFIG_JSON_PATH):
        logging.warning("settings.json no existe: %s", CONFIG_JSON_PATH)
        return []
    try:
        with open(CONFIG_JSON_PATH, "r", encoding="utf-8") as f:
            data = json.load(f)
        out = []
        for it in data.get("monitors", []):
            logf = it.get("logfile", "").strip()
            wh   = it.get("webhook", "").strip()
            if logf:
                out.append({"logfile": logf, "webhook": wh})
        return out
    except Exception as e:
        logging.error("Error leyendo settings.json: %s", e)
        return []

def main():
    cfg_watch = ConfigWatcher(CONFIG_INI_PATH)
    logging.info("GhostMonitor bucle principal en marcha")

    while True:
        monitors = load_settings()
        if not monitors:
            logging.warning("No hay monitores en settings.json; reintento en 30s")
            time.sleep(30)
            continue

        stop_events, threads = [], []
        for it in monitors:
            ev = threading.Event()
            t  = threading.Thread(target=worker,
                                  args=(it["logfile"], it["webhook"], cfg_watch, ev),
                                  daemon=True)
            t.start()
            threads.append(t)
            stop_events.append(ev)

        try:
            while any(t.is_alive() for t in threads):
                time.sleep(1)
        except KeyboardInterrupt:
            logging.info("Interrupción manual, deteniendo hilos")
            break
        finally:
            for ev in stop_events: ev.set()
            for t in threads: t.join(timeout=2)

        logging.info("Todos los monitores terminaron; reintento en 30s")
        time.sleep(30)

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        logging.exception("Fatal: %s", e)
        sys.exit(1)
