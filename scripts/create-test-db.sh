#!/usr/bin/env bash
# Cria o banco de testes (taigo_test) num volume do MySQL que já existia.
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" 2>/dev/null' < docker/mysql/init/01-test-database.sql
echo "banco taigo_test pronto"
