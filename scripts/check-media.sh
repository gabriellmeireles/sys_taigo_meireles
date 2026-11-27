#!/usr/bin/env bash
# Resume as imagens processadas e confere se uma variante é servida pelo nginx.
set -uo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -t -e "
  SELECT m.original_filename AS arquivo, m.width AS larg, m.height AS alt, m.dominant_color AS cor,
         m.processing_status AS status, COUNT(v.id) AS variantes,
         ROUND(m.size_bytes/1024) AS original_kb,
         ROUND(SUM(CASE WHEN v.variant=\"large\" AND v.format=\"webp\" THEN v.size_bytes END)/1024) AS webp1600_kb
  FROM media_files m LEFT JOIN media_variants v ON v.media_file_id = m.id
  GROUP BY m.id ORDER BY m.id" 2>/dev/null'
path=$(docker compose exec -T db sh -c 'mysql -utaigo -p"$MYSQL_PASSWORD" taigo -N -e "SELECT path FROM media_variants WHERE variant=\"large\" AND format=\"webp\" LIMIT 1" 2>/dev/null' | tr -d '\r')
echo "GET /storage/$path -> $(curl -s -o /tmp/v.webp -w '%{http_code} %{content_type} %{size_download}B' http://localhost:8000/storage/$path)"
