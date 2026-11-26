#!/usr/bin/env bash
# Prepara o .env do compose e constrói as imagens de desenvolvimento.
set -euo pipefail
cd "$(dirname "$0")/.."
[ -f .env ] || cp .env.example .env
docker compose build api node
docker compose up -d db mailpit
docker compose ps
