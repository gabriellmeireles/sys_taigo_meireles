#!/usr/bin/env bash
# Atalho: roda um comando no workspace Angular dentro do contêiner Node
# (ex.: bash scripts/ng.sh npm install pacote | bash scripts/ng.sh npx ng test ui).
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose run --rm -e NG_CLI_ANALYTICS=false node "$@"
