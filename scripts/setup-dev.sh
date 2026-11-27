#!/usr/bin/env bash
# Prepara o ambiente de desenvolvimento do zero num computador novo (ou após clonar o repositório).
# Pode ser rodado de novo sem problema: o que já existe é mantido.
#
#   bash scripts/setup-dev.sh
#
# Pré-requisitos: WSL2 (Ubuntu) com o projeto DENTRO do WSL (ex.: ~/projetos/taigo-meireles),
# Docker Desktop rodando com a integração WSL ligada, Git.
set -euo pipefail
cd "$(dirname "$0")/.."

step() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[33m  ! %s\033[0m\n' "$*"; }

step "Verificando pré-requisitos"
command -v docker >/dev/null || { echo "Docker não encontrado. Instale o Docker Desktop e ligue a integração com o WSL."; exit 1; }
docker info >/dev/null 2>&1 || { echo "O Docker não está rodando. Abra o Docker Desktop e rode de novo."; exit 1; }
case "$(pwd)" in
  /mnt/*) warn "O projeto está numa pasta do Windows ($(pwd)). Funciona, mas fica bem mais lento: prefira ~/projetos dentro do WSL." ;;
esac

step "Arquivos de ambiente (.env)"
[ -f .env ] || { cp .env.example .env; echo "  criado .env"; }
[ -f backend/.env ] || { cp backend/.env.example backend/.env; echo "  criado backend/.env"; }

step "Construindo as imagens (PHP com Imagick, Node)"
docker compose build api node

step "Subindo banco, Mailpit e LibreTranslate"
docker compose up -d db mailpit libretranslate
printf '  aguardando o MySQL'
for i in $(seq 1 60); do
  [ "$(docker inspect -f '{{.State.Health.Status}}' "$(docker compose ps -q db)" 2>/dev/null)" = healthy ] && break
  printf '.'; sleep 3
done
echo

step "Dependências do backend (Composer)"
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist

if ! grep -qE '^APP_KEY=.+' backend/.env; then
  step "Gerando APP_KEY"
  docker compose run --rm --no-deps api php artisan key:generate --force
fi

step "Dependências do frontend (npm)"
docker compose run --rm -e NG_CLI_ANALYTICS=false node npm ci --no-audit --no-fund

step "Banco de testes, migrações e dados iniciais"
bash scripts/create-test-db.sh
docker compose run --rm api sh -c 'php artisan migrate --seed --force && php artisan storage:link --force >/dev/null 2>&1 || true'

step "Subindo todos os serviços"
docker compose up -d

if [ -d dev-data/series ] && [ -n "$(ls -A dev-data/series 2>/dev/null)" ]; then
  step "Importando obras de teste de dev-data/series"
  bash scripts/import-dev-artworks.sh
  docker compose run --rm api php artisan translations:sync --type=artwork
  echo "  as traduções EN/ES seguem pela fila (o LibreTranslate baixa os modelos na primeira vez)"
else
  warn "dev-data/series não encontrado: copie a pasta das obras de teste e rode 'bash scripts/import-dev-artworks.sh'."
fi

step "Conferindo os serviços"
api_port=$(grep -E '^API_PORT=' .env | cut -d= -f2); api_port=${api_port:-8000}
site_port=$(grep -E '^SITE_PORT=' .env | cut -d= -f2); site_port=${site_port:-4200}
admin_port=$(grep -E '^ADMIN_PORT=' .env | cut -d= -f2); admin_port=${admin_port:-4201}
for url in "http://localhost:${api_port}/up" "http://localhost:${site_port}/" "http://localhost:${admin_port}/"; do
  code=000
  for i in $(seq 1 60); do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$url" || true)
    [ "$code" = 200 ] && break
    sleep 3
  done
  printf '  %-32s %s\n' "$url" "$code"
done

cat <<EOF

Pronto.
  Site      http://localhost:${site_port}
  Painel    http://localhost:${admin_port}   (login: backend/database/seeders/DevAdminSeeder.php)
  API       http://localhost:${api_port}
  E-mails   http://localhost:8025
Testes: bash scripts/test.sh  ·  bash scripts/test-frontend.sh
EOF
