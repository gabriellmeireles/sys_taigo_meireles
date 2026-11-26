#!/usr/bin/env bash
# Cria o projeto Laravel em ./backend usando o contêiner PHP (executar uma única vez).
set -euo pipefail
cd "$(dirname "$0")/.."
if [ -d backend ]; then echo "backend/ já existe"; exit 1; fi
docker compose run --rm --no-deps -w /var/www api sh -c '
  php -v | head -1
  php -m | grep -iE "^(gd|intl|pdo_mysql|exif|zip)$" | tr "\n" " "; echo
  composer create-project laravel/laravel backend --prefer-dist --no-interaction
  cd backend && php artisan --version
'
