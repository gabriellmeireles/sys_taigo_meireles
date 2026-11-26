#!/usr/bin/env bash
# Testa as regras do banco (scripts/sql/smoke-constraints.sql) e o rollback/reaplicação das migrações.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo --force -N 2>&1' < scripts/sql/smoke-constraints.sql | grep -v Warning
echo "--- rollback e reaplicação"
docker compose run --rm api sh -c 'php artisan migrate:rollback --force && php artisan migrate --seed --force' 2>&1 | grep -E 'DONE|FAIL|SQLSTATE' | sed 's/ \.\.\.*/ /'
