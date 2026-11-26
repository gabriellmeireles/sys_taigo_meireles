#!/usr/bin/env bash
# Envia um e-mail de teste pela fila e confere se chegou no Mailpit.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose logs --tail 5 worker
docker compose exec -T api php artisan tinker --execute="Illuminate\Support\Facades\Mail::raw('Teste do ambiente Docker', fn (\$m) => \$m->to('contato@taigomeireles.com')->subject('Teste Mailpit'));"
sleep 2
curl -s http://localhost:8025/api/v1/messages | grep -o '"Subject":"[^"]*"' | head -3
