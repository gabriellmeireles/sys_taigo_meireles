#!/usr/bin/env bash
# Ajusta o .env do Laravel para o ambiente Docker (MySQL, Mailpit, fila em banco).
set -euo pipefail
cd "$(dirname "$0")/../backend"

set_env() { # set_env CHAVE valor
  if grep -qE "^#? ?$1=" .env; then sed -i -E "s|^#? ?$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
}

set_env APP_NAME '"Taigo Meireles"'
set_env APP_URL http://localhost:8000
set_env APP_LOCALE pt_BR
set_env APP_FALLBACK_LOCALE en
set_env APP_FAKER_LOCALE pt_BR
set_env DB_CONNECTION mysql
set_env DB_HOST db
set_env DB_PORT 3306
set_env DB_DATABASE taigo
set_env DB_USERNAME taigo
set_env DB_PASSWORD taigo
set_env SESSION_LIFETIME 60
set_env MAIL_MAILER smtp
set_env MAIL_HOST mailpit
set_env MAIL_PORT 1025
set_env MAIL_FROM_ADDRESS '"no-reply@taigomeireles.com"'
set_env SITE_URL http://localhost:4200
set_env ADMIN_URL http://localhost:4201
set_env MEDIA_MAX_UPLOAD_MB 30
set_env SITE_TIMEZONE America/Sao_Paulo

# Mesmos valores no .env.example (sem a APP_KEY)
cp .env .env.example.tmp
sed -i -E 's|^APP_KEY=.*|APP_KEY=|' .env.example.tmp
mv .env.example.tmp .env.example

rm -f database/database.sqlite
