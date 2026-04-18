#!/bin/bash
# =============================================================================
# setup.sh – Inizializzazione ambiente Docker per SQL Injection Demo
# Testato su Ubuntu 20.04 / 22.04 / 24.04 e Debian 11 / 12
# =============================================================================

set -euo pipefail

# -----------------------------------------------------------------------------
# Colori per l'output
# -----------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log()     { echo -e "${GREEN}[✔]${NC} $1"; }
info()    { echo -e "${BLUE}[→]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }
error()   { echo -e "${RED}[✘]${NC} $1"; exit 1; }

# -----------------------------------------------------------------------------
# Controllo: deve girare come utente normale (non root)
# -----------------------------------------------------------------------------
if [ "$EUID" -eq 0 ]; then
    error "Non eseguire questo script come root. Usare un utente normale con sudo."
fi

# -----------------------------------------------------------------------------
# Rileva la distribuzione
# -----------------------------------------------------------------------------
if [ ! -f /etc/os-release ]; then
    error "Impossibile determinare la distribuzione Linux."
fi

. /etc/os-release

case "$ID" in
    ubuntu|debian)
        DISTRO="$ID"
        ;;
    *)
        error "Distribuzione '$ID' non supportata. Questo script supporta Ubuntu e Debian."
        ;;
esac

info "Distribuzione rilevata: ${PRETTY_NAME}"

# -----------------------------------------------------------------------------
# 1. Rimuovi completamente eventuali versioni precedenti di Docker
# -----------------------------------------------------------------------------
info "Rimozione di eventuali installazioni precedenti di Docker..."

# Ferma i servizi se attivi
sudo systemctl stop docker docker.socket containerd 2>/dev/null || true

# Rimuovi tutti i pacchetti Docker esistenti (sia docker.io che docker-ce)
sudo apt-get purge -y \
    docker \
    docker-engine \
    docker.io \
    docker-ce \
    docker-ce-cli \
    docker-ce-rootless-extras \
    containerd \
    containerd.io \
    runc \
    docker-compose \
    docker-buildx-plugin \
    docker-compose-plugin \
    2>/dev/null || true

sudo apt-get autoremove -y 2>/dev/null || true

# Rimuovi file residui che potrebbero causare conflitti al riavvio di dockerd
sudo rm -rf /var/lib/docker
sudo rm -rf /var/lib/containerd
sudo rm -f /var/run/docker.sock
sudo rm -f /etc/docker/daemon.json

log "Pulizia completata."

# -----------------------------------------------------------------------------
# 2. Installa le dipendenze necessarie
# -----------------------------------------------------------------------------
info "Installazione dipendenze..."
sudo apt-get update -qq
sudo apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release
log "Dipendenze installate."

# -----------------------------------------------------------------------------
# 3. Aggiungi la chiave GPG ufficiale di Docker
# -----------------------------------------------------------------------------
info "Aggiunta chiave GPG Docker..."
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL "https://download.docker.com/linux/${DISTRO}/gpg" \
    | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
log "Chiave GPG aggiunta."

# -----------------------------------------------------------------------------
# 4. Aggiungi il repository ufficiale Docker
# -----------------------------------------------------------------------------
info "Aggiunta repository Docker..."
echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/${DISTRO} \
${VERSION_CODENAME} stable" \
    | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
log "Repository aggiunto."

# -----------------------------------------------------------------------------
# 5. Installa Docker Engine + Compose plugin
# -----------------------------------------------------------------------------
info "Aggiornamento lista pacchetti e installazione Docker..."
sudo apt-get update -qq
sudo apt-get install -y \
    docker-ce \
    docker-ce-cli \
    containerd.io \
    docker-buildx-plugin \
    docker-compose-plugin
log "Docker installato."

# -----------------------------------------------------------------------------
# 6. Abilita e avvia il servizio Docker
# -----------------------------------------------------------------------------
info "Abilitazione servizio Docker..."

start_docker_systemd() {
    # Reset di eventuali stati di errore precedenti
    sudo systemctl reset-failed docker.service docker.socket 2>/dev/null || true

    # Abilita entrambe le unit
    sudo systemctl enable docker.socket docker.service 2>/dev/null

    # Avvia prima il socket (socket activation), poi il servizio
    sudo systemctl start docker.socket 2>/dev/null || true
    sudo systemctl start docker.service 2>/dev/null

    # Verifica che docker risponda effettivamente
    for i in $(seq 1 15); do
        if sudo docker info &>/dev/null 2>&1; then
            return 0
        fi
        sleep 1
    done
    return 1
}

start_docker_direct() {
    # Fallback: avvio diretto di dockerd senza systemd
    if sudo docker info &>/dev/null 2>&1; then
        log "Docker è già in esecuzione."
        return 0
    fi

    info "Avvio dockerd in background (fallback senza systemd)..."
    sudo dockerd &>/tmp/dockerd.log &
    DOCKERD_PID=$!

    for i in $(seq 1 30); do
        if sudo docker info &>/dev/null 2>&1; then
            log "dockerd avviato (PID: $DOCKERD_PID)."
            return 0
        fi
        sleep 1
    done

    error "dockerd non risponde dopo 30s. Log: /tmp/dockerd.log"
}

SYSTEMD_OK=false
if command -v systemctl &>/dev/null && systemctl is-system-running 2>/dev/null | grep -qE "running|degraded"; then
    if start_docker_systemd; then
        log "Servizio Docker avviato tramite systemd."
        SYSTEMD_OK=true
    else
        warning "systemd presente ma avvio Docker fallito, provo avvio diretto..."
    fi
fi

if [ "$SYSTEMD_OK" = false ]; then
    start_docker_direct
    warning "Docker avviato in modalità diretta: non si riavvierà automaticamente al reboot."
    warning "Per riavviarlo in futuro esegui: sudo dockerd &"
fi

# -----------------------------------------------------------------------------
# 7. Permetti all'utente corrente di eseguire Docker senza sudo
# -----------------------------------------------------------------------------
info "Aggiunta dell'utente '${USER}' al gruppo docker..."
sudo usermod -aG docker "$USER"
log "Utente aggiunto al gruppo docker."



# -----------------------------------------------------------------------------
# 8. Configura il file .env
# -----------------------------------------------------------------------------

# Funzione per leggere una password con conferma (input nascosto)
read_password() {
    local prompt="$1"
    local var_name="$2"
    local pass1 pass2

    while true; do
        read -r -s -p "    $prompt: " pass1
        echo ""
        read -r -s -p "    Conferma $prompt: " pass2
        echo ""
        if [ "$pass1" = "$pass2" ]; then
            if [ -z "$pass1" ]; then
                warning "La password non può essere vuota. Riprova."
            else
                eval "$var_name=\"$pass1\""
                break
            fi
        else
            warning "Le password non coincidono. Riprova."
        fi
    done
}

# Funzione per leggere un valore semplice con default
read_value() {
    local prompt="$1"
    local var_name="$2"
    local default="$3"
    local value

    read -r -p "    $prompt [default: $default]: " value
    if [ -z "$value" ]; then
        eval "$var_name=\"$default\""
    else
        eval "$var_name=\"$value\""
    fi
}

if [ ! -f .env ]; then
    if [ ! -f .env.example ]; then
        error "File .env.example non trovato. Assicurati di eseguire lo script dalla root del repository."
    fi

    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    info "Configurazione credenziali database"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    read_value  "Nome del database"        DB_NAME   "sql_injection_demo"
    echo ""
    read_value  "Username applicazione"    DB_USER   "demo_user"
    read_password "Password applicazione"  DB_PASS
    echo ""
    read_password "Password root MariaDB"  DB_ROOT_PASS

    echo ""
    info "Creazione file .env..."
    cat > .env << EOF
# Credenziali MariaDB
# Generato automaticamente da setup.sh
MYSQL_ROOT_PASSWORD=${DB_ROOT_PASS}
MYSQL_DATABASE=${DB_NAME}
MYSQL_USER=${DB_USER}
MYSQL_PASSWORD=${DB_PASS}
EOF

    log "File .env creato."
    echo ""
else
    info "File .env già esistente, skip configurazione credenziali."
fi

info "Avvio dei container con --build..."

# Carica le variabili dal .env nell'ambiente corrente
set -a
# shellcheck source=.env
. ./.env
set +a

# sg esegue il comando direttamente nel gruppo docker senza aprire
# una shell interattiva, a differenza di newgrp che blocca lo script
sg docker -c "docker compose up --build -d"

# -----------------------------------------------------------------------------
# Riepilogo
# -----------------------------------------------------------------------------
echo ""
echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN}  Installazione e avvio completati con successo!${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
docker --version
docker compose version
echo ""
info "Stato dei container:"
sg docker -c "docker compose ps"
echo ""
info "L'ambiente è raggiungibile su:"
echo "    https://localhost          → Sito demo"
echo "    https://pma.localhost      → phpMyAdmin"
echo ""
echo -e "${YELLOW}[!]${NC} Il certificato SSL è self-signed: il browser mostrerà un avviso."
echo -e "${YELLOW}[!]${NC} Accetta l'eccezione per procedere."
echo ""
echo -e "${YELLOW}[!]${NC} NOTA: il gruppo docker è attivo nelle nuove sessioni di terminale."
echo -e "${YELLOW}[!]${NC} Per usarlo subito nella sessione corrente: newgrp docker"
echo ""
