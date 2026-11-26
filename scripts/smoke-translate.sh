#!/usr/bin/env bash
# Sobe o LibreTranslate, espera os modelos carregarem e traduz um texto de teste PT -> EN/ES.
set -uo pipefail
cd "$(dirname "$0")/.."
grep -q '^LIBRETRANSLATE_PORT=' .env || echo 'LIBRETRANSLATE_PORT=5000' >> .env
docker compose up -d libretranslate 2>&1 | tail -1
echo "aguardando modelos (a primeira vez baixa os idiomas)..."
for i in $(seq 1 80); do
  curl -sf http://localhost:5000/languages >/dev/null && break
  sleep 10
done
curl -s http://localhost:5000/languages | grep -o '"code":"[a-z-]*"' | tr '\n' ' '; echo
for target in en es; do
  curl -s -X POST http://localhost:5000/translate -H 'Content-Type: application/json' \
    -d "{\"q\":\"<p>Óleo sobre tela. A obra faz parte da série <em>Horizonte</em>.</p>\",\"source\":\"pt\",\"target\":\"$target\",\"format\":\"html\"}"
  echo
done
docker stats --no-stream --format '{{.Name}} {{.MemUsage}}' taigo-meireles-libretranslate-1
