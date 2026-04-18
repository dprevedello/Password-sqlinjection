#!/bin/sh
# Genera un certificato self-signed se non esiste già

CERT_DIR=/etc/nginx/certs
CERT="$CERT_DIR/selfsigned.crt"
KEY="$CERT_DIR/selfsigned.key"

mkdir -p "$CERT_DIR"

if [ ! -f "$CERT" ] || [ ! -f "$KEY" ]; then
    echo "[nginx-entrypoint] Generazione certificato self-signed..."
    openssl req -x509 -nodes -newkey rsa:2048 \
        -keyout "$KEY" \
        -out "$CERT" \
        -days 3650 \
        -subj "/C=IT/ST=Local/L=Local/O=SQLInjectionDemo/CN=localhost"
    echo "[nginx-entrypoint] Certificato generato in $CERT_DIR"
else
    echo "[nginx-entrypoint] Certificato già presente, skip."
fi

# Avvia nginx in foreground
exec nginx -g "daemon off;"
