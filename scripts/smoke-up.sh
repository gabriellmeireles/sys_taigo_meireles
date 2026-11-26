#!/usr/bin/env bash
# Sobe todo o ambiente e verifica se cada serviço responde.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose up -d --build 2>&1 | tail -3
echo "aguardando o Angular compilar..."
for i in $(seq 1 60); do
  s=$(curl -s -o /dev/null -w '%{http_code}' http://localhost:4200/ || true)
  a=$(curl -s -o /dev/null -w '%{http_code}' http://localhost:4201/ || true)
  [ "$s" = 200 ] && [ "$a" = 200 ] && break
  sleep 5
done
printf 'API  /up       %s\n' "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/up)"
printf 'Site :4200     %s\n' "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:4200/)"
printf 'Painel :4201   %s\n' "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:4201/)"
printf 'Mailpit :8025  %s\n' "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8025/)"
docker compose exec -T api php -r 'echo "upload_max_filesize=", ini_get("upload_max_filesize"), " memory_limit=", ini_get("memory_limit"), PHP_EOL;'
docker compose ps --format 'table {{.Service}}\t{{.Status}}'
