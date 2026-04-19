#!/bin/sh
set -e

echo "[entrypoint] Inizializzazione bcrypt per users_ex4..."
php /usr/local/bin/init_bcrypt.php

echo "[entrypoint] Avvio Apache..."
exec docker-php-entrypoint apache2-foreground
