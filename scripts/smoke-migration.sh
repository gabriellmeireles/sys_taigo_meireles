#!/usr/bin/env bash
# Aplica as migrações pendentes, desfaz a última, reaplica e mostra a retenção configurada.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm api sh -c 'php artisan migrate --force && php artisan migrate:rollback --step=1 --force && php artisan migrate --force' 2>&1 | grep -E 'DONE|FAIL|SQLSTATE' | sed 's/ \.\.\.*/ /'
docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -N -e "SELECT CONCAT(\"message_retention_days=\", message_retention_days) FROM site_settings" 2>/dev/null'
