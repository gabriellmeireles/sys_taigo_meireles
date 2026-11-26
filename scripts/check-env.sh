#!/usr/bin/env bash
# Mostra UID/GID do usuário e verifica se as portas do ambiente estão livres.
echo "UID=$(id -u) GID=$(id -g)"
for p in 3306 8000 8025 1025 4200 4201; do
  if ss -ltn 2>/dev/null | grep -q ":$p "; then echo "porta $p: OCUPADA"; else echo "porta $p: livre"; fi
done
