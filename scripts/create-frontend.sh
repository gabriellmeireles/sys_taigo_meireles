#!/usr/bin/env bash
# Cria o workspace Angular em ./frontend com os apps "site" e "admin" e a biblioteca "ui"
# (executar uma única vez). Roda dentro do contêiner Node.
set -euo pipefail
cd "$(dirname "$0")/.."
if [ -f frontend/angular.json ]; then echo "frontend/ já existe"; exit 1; fi
mkdir -p frontend
docker compose run --rm -e NG_CLI_ANALYTICS=false -w /var/www/root node sh -c '
  node -v
  npx -y @angular/cli@latest new frontend --no-create-application --skip-git --package-manager=npm --defaults
  cd frontend
  npx ng version | grep -E "Angular CLI|^Node|Angular:" || true
  npx ng generate application site  --style=scss --routing --ssr=false --prefix=tm --defaults
  npx ng generate application admin --style=scss --routing --ssr=false --prefix=adm --defaults
  npx ng generate library ui --prefix=ui --defaults
'
