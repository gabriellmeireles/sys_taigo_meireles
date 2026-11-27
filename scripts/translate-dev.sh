#!/usr/bin/env bash
# Agenda as traduções automáticas das obras locais e mostra o resultado (LibreTranslate real).
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose up -d libretranslate >/dev/null 2>&1
docker compose restart worker >/dev/null 2>&1
docker compose run --rm api php artisan translations:sync --type=artwork 2>&1 | grep -v Container
echo "aguardando a fila..."
for i in $(seq 1 40); do
  left=$(docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -N -e "SELECT COUNT(*) FROM jobs" 2>/dev/null' | tr -d '\r')
  [ "$left" = "0" ] && break
  sleep 3
done
docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -t -e "
  SELECT pt.title AS portugues, en.title AS ingles, en.translation_status AS en_estado, es.title AS espanhol, es.translation_status AS es_estado
  FROM artwork_translations pt
  LEFT JOIN artwork_translations en ON en.artwork_id = pt.artwork_id AND en.locale_code = \"en\"
  LEFT JOIN artwork_translations es ON es.artwork_id = pt.artwork_id AND es.locale_code = \"es\"
  WHERE pt.locale_code = \"pt-BR\" ORDER BY pt.artwork_id;
  SELECT description FROM artwork_translations WHERE artwork_id = 1;
  SELECT integration_key, status, provider_label, last_error FROM integration_states WHERE integration_key = \"translation\"" 2>/dev/null'
