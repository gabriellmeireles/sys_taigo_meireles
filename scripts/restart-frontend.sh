#!/usr/bin/env bash
# Reinicia os servidores do Angular (necessário após mudar angular.json ou instalar pacotes).
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose restart site admin >/dev/null 2>&1
for url in http://localhost:4200/design-system http://localhost:4201/; do
  code=000
  for i in $(seq 1 40); do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$url")
    [ "$code" = 200 ] && break
    sleep 3
  done
  echo "$url -> $code"
done
