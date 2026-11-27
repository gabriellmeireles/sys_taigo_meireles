#!/usr/bin/env bash
# Gera os arquivos da marca a partir do PDF de variações do logotipo (fonte vetorial).
#   uso: bash scripts/brand/build-brand-assets.sh "/caminho/Taigo Meireles - Logotipo - Variações.pdf"
# Saída:
#   frontend/projects/ui/brand/{mao,selo,horizontal,assinatura}.svg  (uma cor: currentColor)
#   frontend/projects/{site,admin}/public/{favicon.svg,favicon.ico,apple-touch-icon.png,icon-512.png}
# O .ai foi salvo sem compatibilidade com PDF, por isso a fonte é o PDF de variações
# (linha sem fundo, última da prancheta). As coordenadas abaixo são as dessa prancheta.
set -euo pipefail
cd "$(dirname "$0")/../.."
PDF="${1:?informe o caminho do PDF de variações}"
WORK=$(mktemp -d)
cp "$PDF" "$WORK/variacoes.pdf"

# nome x y largura altura (pontos, a partir do canto superior esquerdo)
CROPS='mao 246 6548 361 512
selo 1393 6495 617 617
horizontal 2624 6739 707 130
assinatura 3915 6688 676 232'

docker run --rm -v "$WORK":/w debian:bookworm-slim sh -c "
  apt-get update -qq >/dev/null && apt-get install -y -qq poppler-utils imagemagick >/dev/null 2>&1
  echo '$CROPS' | while read n x y w h; do
    pdftocairo -svg -x \$x -y \$y -W \$w -H \$h -paperw \$w -paperh \$h /w/variacoes.pdf /w/\$n.raw.svg
  done
  # Mão em alta resolução, com fundo transparente, para os ícones
  pdftocairo -png -singlefile -r 300 -x 1025 -y 27283 -W 1504 -H 2133 /w/variacoes.pdf /w/mao-alta
  # O recorte tem fundo branco desenhado no PDF: o tom de cinza invertido vira a transparência
  # (traço preto -> opaco, fundo branco -> transparente) e o traço é pintado de branco.
  convert /w/mao-alta.png -colorspace Gray -negate /w/alpha.png
  convert -size 1504x2133 xc:white /w/alpha.png -alpha off -compose CopyOpacity -composite /w/mao-branca.png
  convert -size 1024x1024 xc:'#262626' \( /w/mao-branca.png -resize x720 \) -gravity center -composite /w/icone-1024.png
  convert /w/icone-1024.png -resize 512x512 /w/icon-512.png
  convert /w/icone-1024.png -resize 180x180 /w/apple-touch-icon.png
  convert /w/icone-1024.png -define icon:auto-resize=48,32,16 /w/favicon.ico
"

# Limpeza dos SVGs: sem fundo branco, preto -> currentColor, sem tamanho fixo
for n in mao selo horizontal assinatura; do
  sed -E \
    -e '/<rect x="0" y="0"[^>]*fill:rgb\(100%,100%,100%\)[^>]*\/>/d' \
    -e 's/fill:rgb\(0%,0%,0%\)/fill:currentColor/g' \
    -e 's/ width="[0-9.]+pt" height="[0-9.]+pt"//' \
    "$WORK/$n.raw.svg" > "$WORK/$n.svg"
done

# Otimização (svgo) no contêiner Node do projeto
cp "$WORK"/{mao,selo,horizontal,assinatura}.svg frontend/projects/ui/brand/ 2>/dev/null || {
  mkdir -p frontend/projects/ui/brand && cp "$WORK"/{mao,selo,horizontal,assinatura}.svg frontend/projects/ui/brand/
}
docker compose run --rm -T -w /var/www/frontend node npx -y svgo@3 --multipass --quiet \
  -f projects/ui/brand -o projects/ui/brand

# Favicon SVG: mão em #262626, branca quando o navegador está em tema escuro
FAV=$(sed -e 's/fill:currentColor/fill:var(--c)/g' -e 's#<svg #<svg #' frontend/projects/ui/brand/mao.svg \
  | sed -E 's#(<svg[^>]*>)#\1<style>:root{--c:\#262626}@media (prefers-color-scheme:dark){:root{--c:\#fff}}</style>#')
for app in site admin; do
  printf '%s\n' "$FAV" > "frontend/projects/$app/public/favicon.svg"
  cp "$WORK"/{favicon.ico,apple-touch-icon.png,icon-512.png} "frontend/projects/$app/public/"
done

ls -la frontend/projects/ui/brand frontend/projects/site/public | grep -vE '^total|^d'
rm -rf "$WORK"
