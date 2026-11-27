#!/usr/bin/env bash
# Reconstrói a imagem PHP (api e worker), sobe de novo e mostra formatos de imagem suportados.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose build api worker 2>&1 | tail -2
docker compose up -d api worker 2>&1 | tail -1
docker compose exec -T api php -r 'echo "Imagick: ", implode(",", array_intersect(["JPEG","PNG","TIFF","WEBP"], Imagick::queryFormats())), PHP_EOL;'
