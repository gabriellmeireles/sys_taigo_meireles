#!/usr/bin/env bash
# Executa um arquivo SQL no banco local de desenvolvimento (ex.: bash scripts/sql-run.sh scripts/sql/x.sql).
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -t 2>/dev/null' < "$1"
