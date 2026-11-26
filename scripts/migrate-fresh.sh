#!/usr/bin/env bash
# Recria o banco de desenvolvimento do zero (APAGA os dados locais) e roda os seeders.
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm api php artisan migrate:fresh --seed --force
docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -N -e "SELECT COUNT(*) AS tabelas FROM information_schema.tables WHERE table_schema = DATABASE(); SELECT code, currency_code FROM locales; SELECT email FROM admins; SELECT locale_code FROM auto_reply_templates;"'
