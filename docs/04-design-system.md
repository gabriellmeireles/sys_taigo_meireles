# 5. Design system visual

Nome de trabalho: **Atelier DS**. Um único conjunto de tokens atende site e painel; o painel usa a mesma base com densidade maior.

## 5.1 Cores

| Token | Hex | Uso principal |
|---|---|---|
| `color.ink` | `#000000` | Botão primário, foco, texto de máximo contraste, fundo do visualizador de obras |
| `color.charcoal` | `#262626` | Texto padrão, rodapé, sidebar do painel |
| `color.graphite` | `#6F6D6E` | Texto secundário, legendas, bordas de campos, ícones |
| `color.stone` | `#CCC6C3` | Divisores, bordas decorativas, estado desabilitado, texto sobre fundo escuro |
| `color.mist` | `#E7E3E2` | Fundos de seção, molduras de obras, skeletons, hover de linhas |
| `color.paper` | `#FFFFFF` | Fundo principal |

### Contraste (WCAG 2.2) — calculado
| Combinação | Razão aprox. | Uso permitido |
|---|---|---|
| `#262626` sobre `#FFFFFF` | 15,1:1 | Todo texto |
| `#000000` sobre `#FFFFFF` | 21:1 | Todo texto |
| `#6F6D6E` sobre `#FFFFFF` | 5,1:1 | Texto normal (AA) |
| `#6F6D6E` sobre `#E7E3E2` | 4,0:1 | **Só** texto grande (≥24px ou ≥19px negrito) e bordas/ícones (≥3:1) |
| `#CCC6C3` sobre `#FFFFFF` | 1,7:1 | **Nunca** para texto nem borda de campo; só decoração |
| `#FFFFFF` sobre `#262626` | 15,1:1 | Todo texto |
| `#CCC6C3` sobre `#262626` | 8,9:1 | Texto secundário em fundos escuros |

### Cores funcionais (P-02, decidido)
A paleta é toda neutra. Erro e sucesso são comunicados por **ícone + texto + espessura de borda**, nunca só por cor. Está aprovada como opção **uma** cor funcional discreta apenas para erro (`#8A2C2C`, 7,6:1 sobre branco), usada em texto de erro e borda de campo inválido. Sem ela, o erro usa `#000` com borda 2px e ícone.

## 5.2 Tipografia

- **Títulos:** Cinzel Regular (400). Cinzel é desenhada em maiúsculas e versaletes — não há minúsculas reais; títulos longos ficam extensos, então limitar linhas (ver regras).
- **Texto e interface:** no site, Open Sans Regular (400), com hierarquia por tamanho, cor, caixa-alta e espaçamento entre letras — sem negrito. No painel, Open Sans com os pesos e variações necessários (P-03, decidido).
- **Carregamento (sugestão):** arquivos WOFF2 hospedados no próprio servidor, com `font-display: swap` e subconjunto latino (inclui acentos PT/ES). Evita chamadas a terceiros (bom para privacidade, dada a ausência de banner de cookies). Ambas as fontes são licenciadas sob OFL.

### Escala (fluida com `clamp`, valores M → D)
| Token | Família | Tamanho | Altura de linha | Tracking | Uso |
|---|---|---|---|---|---|
| `display` | Cinzel | 40 → 72px | 1.1 | 0.04em | Manifesto, título do Início |
| `h1` | Cinzel | 32 → 56px | 1.15 | 0.06em | Título de página |
| `h2` | Cinzel | 26 → 40px | 1.2 | 0.06em | Título de obra, seções |
| `h3` | Cinzel | 20 → 28px | 1.25 | 0.06em | Subseções |
| `h4` | Cinzel | 17 → 20px | 1.3 | 0.08em | Título em card |
| `eyebrow` | Open Sans | 12px, caixa-alta | 1.4 | 0.18em | Rótulos de seção |
| `body-lg` | Open Sans | 18 → 20px | 1.7 | 0 | Biografia, introduções |
| `body` | Open Sans | 16 → 17px | 1.65 | 0 | Texto corrido |
| `body-sm` | Open Sans | 14px | 1.55 | 0.01em | Ficha técnica, metadados |
| `caption` | Open Sans | 13px | 1.5 | 0.02em | Legendas |
| `button` | Open Sans | 13px, caixa-alta | 1 | 0.14em | Botões, navegação |
| `numeric` | Open Sans | 28 → 36px | 1.1 | 0 | KPIs do painel (`font-variant-numeric: tabular-nums`) |

**Regras:** largura de texto corrido 60–72 caracteres; títulos de obra até 3 linhas (mais que isso: reduzir para `h3`); nunca justificar texto; nunca Cinzel abaixo de 16px.

## 5.3 Espaçamento
Base 4px. Tokens: `space-1` 4 · `space-2` 8 · `space-3` 12 · `space-4` 16 · `space-5` 24 · `space-6` 32 · `space-7` 48 · `space-8` 64 · `space-9` 96 · `space-10` 128 · `space-11` 160.

| Contexto | M | T | D |
|---|---|---|---|
| Entre seções da página | 64 | 96 | 128–160 |
| Título → conteúdo | 24 | 32 | 48 |
| Entre cards no grid (vertical) | 32 | 48 | 64 |
| Padding interno de bloco | 20 | 32 | 48 |

## 5.4 Grid e breakpoints

| Breakpoint | Largura | Colunas | Gutter | Margem | Máx. conteúdo |
|---|---|---|---|---|---|
| `xs` | 0–599 | 4 | 16 | 20 | — |
| `sm` | 600–767 | 4 | 16 | 32 | — |
| `md` (tablet) | 768–1023 | 8 | 24 | 40 | — |
| `lg` (tablet horizontal / notebook pequeno) | 1024–1279 | 12 | 24 | 48 | — |
| `xl` (desktop) | 1280–1599 | 12 | 32 | 64 | 1440 |
| `xxl` | ≥1600 | 12 | 32 | auto | 1440 (imagens "sangradas" podem ocupar 100%) |

Grids de obras: `xs/sm` 2 col · `md` 3 col · `lg/xl` 4 col · `xxl` 4 col mais largas. Grid de imagens "sangradas" (destaque, ateliê) ignora as margens.

## 5.5 Forma, borda, sombra, movimento
- **Raio:** 0 em tudo (estética de galeria). Exceção: marcador circular de status e avatar.
- **Bordas:** `1px solid` — `#CCC6C3` para divisores; `#6F6D6E` para campos; `#000` para botão secundário.
- **Sombra:** nenhuma no site. No painel, apenas em sobreposições (menu, diálogo): `0 8px 32px rgba(0,0,0,.12)`.
- **Movimento:** 150 ms (hover), 250 ms (entrada de elementos, fade + 8px de deslocamento), 400 ms (abrir visualizador). Curva `cubic-bezier(.2,.0,.0,1)`. Com `prefers-reduced-motion`, só fade de 100 ms.
- **Foco:** `outline: 2px solid #000; outline-offset: 3px` (em fundo escuro, `#FFF`). Sempre visível ao navegar por teclado.

## 5.6 Imagens
- Nunca cortar a obra: `object-fit: contain` dentro de moldura com proporção fixa no grid (4:5) e fundo `#FFFFFF` ou `#E7E3E2` (configurável); na página da obra, proporção real.
- Fotos de ambiente/ateliê/artista podem usar `cover` com ponto focal definido no painel (sugestão: campos `focal_x`, `focal_y`).
- `srcset` com larguras geradas (480, 960, 1600, 2400) e `sizes` corretos; `loading="lazy"` exceto a imagem principal da tela; `width`/`height` sempre informados (evita deslocamento de layout).
- Placeholder: cor dominante da imagem (`dominant_color` salvo no banco) enquanto carrega.

## 5.7 Componentes

### Botões
| Variante | Aparência | Uso |
|---|---|---|
| `primary` | Fundo `#000`, texto `#FFF`, 48px de altura (M: largura total), padding 0 32px | Ação principal (Consultar pelo WhatsApp, Enviar) |
| `secondary` | Borda 1px `#000`, texto `#000`, fundo transparente | Ação alternativa (Instagram, Ver exposição) |
| `inverse` | Fundo `#FFF`, texto `#000` (em fundos escuros) | CTA na faixa escura |
| `link` | Texto com sublinhado 1px deslocado 4px; seta → | Navegação secundária |
| `icon` | 44×44px mínimo, rótulo acessível | Fechar, zoom, menu |
| `channel` | `secondary` + ícone de WhatsApp/Instagram/e-mail à esquerda | Ações de contato |

Estados: **hover** (primary → `#262626`; secondary → fundo `#000` texto `#FFF`; link → sublinhado cresce), **focus-visible** (contorno), **active** (deslocamento 1px), **disabled** (fundo `#E7E3E2`, texto `#6F6D6E`, cursor padrão, `aria-disabled`), **loading** (rótulo vira "Enviando…" + linha animada de 2px na base; largura fixa para não pular).

### Formulários
- Campo com rótulo acima (`body-sm`, `#262626`), caixa com borda 1px `#6F6D6E`, altura 48px, padding 0 16px, fundo branco.
- Estados: **foco** borda `#000` 2px; **erro** borda 2px (`#000` ou cor funcional) + ícone ⚠ + mensagem abaixo em `body-sm`, `aria-describedby`; **sucesso** (só quando útil, ex.: e-mail validado) ícone ✓; **desabilitado** fundo `#E7E3E2`; **somente leitura** sem borda, fundo `#E7E3E2`.
- Obrigatório: "*" após o rótulo + legenda "* obrigatório" no topo do formulário.
- `textarea` mínimo 5 linhas, contador de caracteres quando houver limite.
- Select/dropdown de filtro: botão com rótulo + contador (`Técnica (2)`), abre lista com checkboxes; teclado completo.
- Checkbox/radio: quadrado/círculo 20px, borda `#262626`, marcado preenchido `#000`.
- Toggle (painel): trilho 40×22, ligado `#000`.

### Cartões
- `ArtworkCard`: moldura 4:5 + título (`h4`) + `ano · técnica` (`body-sm` grafite) + `StatusBadge` + preço opcional. Hover (D): imagem escala 1.02 em 400 ms e título ganha sublinhado. Área clicável é o card inteiro (um único link).
- `ExhibitionCard`: imagem 3:2 + eyebrow com datas + título + local.
- `PressCard`: horizontal (capa 1:1 à esquerda) com veículo, título, ações.
- `StudioTile`: imagem/vídeo com legenda abaixo.
- `KpiTile` (painel): rótulo, número, variação.

### Indicadores
- `StatusBadge` (disponível ● / reservada ◐ / vendida ○) — sempre com texto; tamanho `caption`, caixa-alta.
- `TranslationBadge` (painel): `Revisada` (borda preta) · `Automática` (borda pontilhada) · `Desatualizada` (fundo `#E7E3E2` + ⚠) · `Ausente` (traço).
- `Chip` de filtro ativo com botão remover.

### Navegação
`SiteHeader`, `MobileMenuOverlay`, `LanguageSwitcher` (`PT | EN | ES`, idioma atual sublinhado, `lang` e `hreflang` nos links), `Breadcrumb`, `FilterBar`, `LoadMoreButton`, `Pagination` (painel), `Tabs` textuais (sublinhado no ativo), `AdminSidebar`, `AdminTopbar`.

### Mídia
`ResponsiveImage`, `ArtworkViewer` (tela cheia + zoom), `Lightbox` (versão simples para fotos), `YouTubeFacade` (miniatura + botão ▶; ao clicar, carrega `youtube-nocookie.com/embed/{id}?autoplay=1`), `InstagramGrid`, `ImageUploader` (arrastar e soltar, reordenação, principal ★), `MediaPicker`.

### Feedback
`Skeleton` (blocos `#E7E3E2` com brilho lento; sem brilho em `reduced-motion`), `Spinner` linear, `EmptyState`, `ErrorState` (com ação de nova tentativa), `SuccessState` (formulário), `Toast`, `InlineAlert` (info/aviso/erro), `ConfirmDialog`, `ProgressBar` (uploads).

### Painel — dados
`DataTable` (cabeçalho fixo, ordenação, seleção, ações por linha, modo cartão em M), `DateRangePicker`, `SegmentedControl`, `LineChart`, `BarList` (barras horizontais), `TranslationField` (origem | tradução), `SeoPreview` (resultado de busca + cartão social), `RichTextEditor` (subconjunto: parágrafo, H2/H3, negrito, itálico, link, lista).

## 5.8 Iconografia
Ícones de traço fino (1.5px), 20/24px, cor herdada. Conjunto mínimo: menu, fechar, buscar, filtro, seta, seta externa ↗, download, zoom +/−, tela cheia, play, WhatsApp, Instagram, e-mail, check, alerta, arrastar, estrela (principal), lixeira, olho. Biblioteca a escolher (sugestão: qualquer conjunto open source de traço fino; marcas de WhatsApp/Instagram conforme diretrizes de marca de cada uma).

## 5.9 Tokens (formato sugerido)
```css
:root {
  --color-ink:#000; --color-charcoal:#262626; --color-graphite:#6F6D6E;
  --color-stone:#CCC6C3; --color-mist:#E7E3E2; --color-paper:#FFF;
  --font-title:'Cinzel', serif; --font-text:'Open Sans', sans-serif;
  --space-1:4px; --space-2:8px; --space-3:12px; --space-4:16px; --space-5:24px;
  --space-6:32px; --space-7:48px; --space-8:64px; --space-9:96px; --space-10:128px; --space-11:160px;
  --radius:0; --control-height:48px; --focus-ring:2px solid var(--color-ink);
  --ease:cubic-bezier(.2,0,0,1); --dur-fast:150ms; --dur-base:250ms; --dur-slow:400ms;
}
```
Sugestão: publicar os tokens e componentes como **biblioteca Angular compartilhada** no mesmo workspace dos dois apps (ver arquitetura §6.3).

## 5.10 Implementação (etapa 2)

A biblioteca `frontend/projects/ui` (importada como `@tm/ui`) implementa este design system:

| Parte | Onde |
|---|---|
| Tokens (variáveis CSS), base, tipografia, grid, botões, formulários e estilos dos componentes | `projects/ui/styles/*.scss`, incluídos no `angular.json` de cada app |
| Fontes Cinzel 400 e Open Sans 400 (painel também 600), hospedadas no próprio projeto, subconjunto latino | pacotes `@fontsource/*`, sem chamadas a terceiros |
| `uiButton` (primary, secondary, inverse, link, channel; carregando; desabilitado) | `lib/button` |
| `ui-form-field` + `uiInput` (rótulo, ajuda, erro, obrigatório; liga `id`, `aria-describedby`, `aria-invalid`) | `lib/form-field` |
| `ui-status-badge`, `ui-translation-badge`, `ui-chip` | `lib/badges` |
| `ui-artwork-card` (moldura 4:5, obra contida, status, preço) | `lib/artwork-card` |
| `ui-skeleton`, `ui-progress`, `ui-state` (vazio, erro, sucesso), `ui-section-heading`, `ui-kpi-tile` | `lib/feedback` |
| `ToastService` + `ui-toast-outlet` (status/alert para leitores de tela) | `lib/toast` |
| `ui-confirm-dialog` (sobre `<dialog>` nativo) | `lib/dialog` |
| `formatPrice`, `displayPrice` (moeda por idioma e regras da §7.5) | `lib/pricing` |
| `ui-logo` (marca: horizontal, assinatura, selo, mão) | `lib/logo` + `projects/ui/brand/*.svg` |

## 5.11 Marca (manual da marca)

O manual confirma a paleta (§5.1, com valores CMYK para impressos) e a tipografia: **Cinzel** principal, **Open Sans** de apoio.

**Formas da marca** — SVGs vetoriais em `frontend/projects/ui/brand`, extraídos do PDF de variações do logotipo (o `.ai` foi salvo sem compatibilidade com PDF). Gerados por `scripts/brand/build-brand-assets.sh`, que também cria o favicon e os ícones.

| Forma | Arquivo | Uso no site e no painel |
|---|---|---|
| Horizontal (mão + nome) | `horizontal.svg` | Cabeçalho do site; menu lateral do painel |
| Assinatura (mão sobre o nome) | `assinatura.svg` | Rodapé; telas de destaque |
| Selo circular | `selo.svg` | Login do painel; imagem padrão de compartilhamento; rodapé |
| Mão ("elemento associativo") | `mao.svg` | Favicon, ícone de celular, 404, carregamento inicial |

**Cor**: os SVGs são de uma cor só e o componente usa a cor do texto (`currentColor`). Combinações do manual:

| Versão | Fundo | Marca |
|---|---|---|
| I (principal) | `#262626` | clara |
| II | `#6F6D6E` | clara |
| III | `#CCC6C3` | escura |
| IV | `#E7E3E2` | grafite |
| Monocromática I | `#FFFFFF` | preta |
| Monocromática II | `#000000` | branca |

**Regras**: a marca nunca vai sobre fotos de obras (requisito de não usar marca d'água); não distorcer, girar, aplicar sombra ou outras cores além da paleta; manter respiro de pelo menos a altura da letra "T" do nome em volta da marca.

**Ícones**: `favicon.svg` (mão em `#262626`, branca em tema escuro do navegador), `favicon.ico` (16/32/48), `apple-touch-icon.png` (180) e `icon-512.png` (mão branca sobre `#262626`).

Vitrine para revisão visual: `http://localhost:4200/design-system` (provisória; sai do site antes da publicação). Classes utilitárias: `ui-container`, `ui-grid`, `ui-section`, `ui-stack`, `ui-cluster`, `ui-artwork-grid`, `ui-eyebrow`, `ui-display`, `ui-prose`, `ui-on-dark`, `ui-visually-hidden`, `ui-skip-link`, `ui-check` (caixa de seleção e opção única), `ui-toggle` (interruptor liga/desliga: checkbox nativo com `role="switch"`, trilho e texto "Ligado/Desligado" vindo de `data-on`/`data-off`).
