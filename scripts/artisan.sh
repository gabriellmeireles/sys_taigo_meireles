#!/usr/bin/env bash
# Atalho: roda um comando dentro do contêiner da API (ex.: bash scripts/artisan.sh php artisan route:list).
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm api "$@"
