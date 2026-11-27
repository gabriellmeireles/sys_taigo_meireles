#!/usr/bin/env bash
# Roda os testes do backend no contêiner da API (ex.: bash scripts/test.sh --filter=Login).
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm api php artisan test "$@"
