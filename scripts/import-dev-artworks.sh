#!/usr/bin/env bash
# Importa as obras de teste de dev-data/series (fora do Git) para o banco local.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm api sh -c 'php artisan storage:link >/dev/null 2>&1; php artisan migrate --force >/dev/null && php artisan dev:import-artworks ../dev-data/series'
