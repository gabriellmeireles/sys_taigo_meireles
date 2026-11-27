#!/usr/bin/env bash
# Testes unitários (Vitest) e build de produção dos projetos Angular.
set -uo pipefail
cd "$(dirname "$0")/.."
status=0
strip() { sed 's/\x1b\[[0-9;]*m//g'; }
for p in ui site admin; do
  echo "=== testes: $p"
  out=$(docker compose run --rm -e NG_CLI_ANALYTICS=false -e CI=true node npx ng test "$p" --watch=false 2>&1) || status=1
  echo "$out" | strip | grep -E 'Test Files|Tests |FAIL|Error' || true
done
for p in site admin; do
  echo "=== build: $p"
  out=$(docker compose run --rm -e NG_CLI_ANALYTICS=false node npx ng build "$p" 2>&1) || status=1
  echo "$out" | strip | grep -E 'Initial total|WARNING|ERROR|Application bundle' | head -20
done
exit $status
