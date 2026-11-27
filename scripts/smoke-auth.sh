#!/usr/bin/env bash
# Teste ponta a ponta do login do painel contra o ambiente rodando (como o navegador faria).
set -uo pipefail
cd "$(dirname "$0")/.."

# Variáveis de sessão/CORS no .env do Laravel (idempotente)
for kv in 'SANCTUM_STATEFUL_DOMAINS=localhost:4200,localhost:4201' 'CORS_ALLOWED_ORIGINS=http://localhost:4200,http://localhost:4201'; do
  k=${kv%%=*}
  for f in backend/.env backend/.env.example; do grep -q "^$k=" "$f" || echo "$kv" >> "$f"; done
done

docker compose run --rm api sh -c 'php artisan migrate --force && php artisan db:seed --class=DevAdminSeeder --force' 2>&1 | grep -E 'DONE|FAIL|SQLSTATE' | sed 's/ \.\.\.*/ /'
docker compose restart api worker >/dev/null 2>&1
for i in $(seq 1 30); do [ "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/up)" = 200 ] && break; sleep 1; done

API=http://localhost:8000
ORIGIN=http://localhost:4201
JAR=$(mktemp)
xsrf() { grep XSRF-TOKEN "$JAR" | awk '{print $7}' | python3 -c 'import sys,urllib.parse;print(urllib.parse.unquote(sys.stdin.read().strip()))'; }

echo "1) cookie CSRF:   $(curl -s -o /dev/null -w '%{http_code}' -c "$JAR" -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" $API/sanctum/csrf-cookie)"
echo "2) login:         $(curl -s -b "$JAR" -c "$JAR" -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "X-XSRF-TOKEN: $(xsrf)" \
      -d '{"email":"admin@taigomeireles.test","password":"senha-dev-12345"}' $API/api/v1/admin/auth/login | head -c 160)"
echo "3) me:            $(curl -s -b "$JAR" -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" -H 'Accept: application/json' $API/api/v1/admin/auth/me | head -c 120)"
echo "4) CORS:          $(curl -s -D - -o /dev/null -H "Origin: $ORIGIN" -X OPTIONS -H 'Access-Control-Request-Method: POST' $API/api/v1/admin/auth/login | grep -iE 'access-control-allow-(origin|credentials)' | tr -d '\r' | tr '\n' ' ')"
echo "5) origem errada: $(curl -s -D - -o /dev/null -H 'Origin: http://evil.test' -X OPTIONS -H 'Access-Control-Request-Method: POST' $API/api/v1/admin/auth/login | grep -ic 'access-control-allow-origin') cabeçalhos CORS"
echo "6) logout:        $(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" -H 'Accept: application/json' -H "X-XSRF-TOKEN: $(xsrf)" $API/api/v1/admin/auth/logout)"
echo "7) me depois:     $(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" -H 'Accept: application/json' $API/api/v1/admin/auth/me)"
echo "8) sem CSRF:      $(curl -s -o /dev/null -w '%{http_code}' -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"email":"x@y.z","password":"x"}' $API/api/v1/admin/auth/login)"

# Esqueci a senha -> fila -> Mailpit
curl -s -o /dev/null -c "$JAR" -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" $API/sanctum/csrf-cookie
curl -s -o /dev/null -b "$JAR" -H "Origin: $ORIGIN" -H "Referer: $ORIGIN/" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "X-XSRF-TOKEN: $(xsrf)" \
  -d '{"email":"admin@taigomeireles.test"}' $API/api/v1/admin/auth/forgot-password
sleep 6
echo "9) Mailpit:       $(curl -s http://localhost:8025/api/v1/messages | grep -o '"Subject":"Redefini[^"]*"' | head -1)"
rm -f "$JAR"
