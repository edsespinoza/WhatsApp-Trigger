#!/bin/sh
set -e

WORK="/var/www/html"

# ── Permissões de storage e cache ─────────────────────────────────────────────
mkdir -p \
    "$WORK/storage/framework/sessions" \
    "$WORK/storage/framework/views" \
    "$WORK/storage/framework/cache/data" \
    "$WORK/bootstrap/cache"

chown -R www-data:www-data \
    "$WORK/storage" \
    "$WORK/bootstrap/cache" 2>/dev/null || true

# ── Aquecer caches do Laravel (reduz leituras de arquivo por request) ─────────
# Só roda no container 'app' (php-fpm), não em queue/scheduler
if [ "$1" = "php-fpm" ]; then
    cd "$WORK"

    # Executa como www-data para que os arquivos de cache tenham a permissão certa
    su -s /bin/sh www-data -c "
        php artisan config:cache  --quiet 2>/dev/null || true
        php artisan route:cache   --quiet 2>/dev/null || true
        php artisan view:cache    --quiet 2>/dev/null || true
    "
    echo "[WhatsTrigger] Caches aquecidos."
fi

exec "$@"
