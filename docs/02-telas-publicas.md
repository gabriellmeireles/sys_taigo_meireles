# 3. Inventário de telas públicas

Convenções dos wireframes: `[ ]` botão, `( )` campo, `▣` imagem, `···` texto corrido.
Breakpoints e grid estão no [Design System](04-design-system.md). **D** = desktop (≥1280), **T** = tablet (768–1279), **M** = celular (<768).

## 3.0 Elementos globais

### Cabeçalho (`SiteHeader`)
- **D:** assinatura "TAIGO MEIRELES" à esquerda (Cinzel 20, tracking 0.2em); navegação à direita em Open Sans 13 caixa-alta, tracking 0.14em; seletor `PT | EN | ES` separado por uma barra fina. Altura 88px; fundo branco; ao rolar, encolhe para 64px com linha inferior `#E7E3E2`.
- **Sobre a obra em destaque (Início):** cabeçalho transparente com texto branco quando a imagem é escura (definido por campo no painel "tom da imagem: clara/escura"), passando a sólido ao rolar.
- **T/M:** assinatura + botão "Menu" (texto, não só ícone). O menu abre **sobreposição em tela cheia** branca, links em Cinzel 32, seletor de idioma e ícones de WhatsApp/Instagram no rodapé do menu. Fecha com `Esc`, botão "Fechar" e ao navegar.

### Rodapé (`SiteFooter`)
```
──────────────────────────────────────────────────────────────────────
TAIGO MEIRELES                        SITE            CONTATO
"Frase curta do artista"              Sobre           WhatsApp ↗
                                      Galeria         Instagram ↗
                                      Disponíveis     contato@…
                                      Exposições
                                      Ateliê          IDIOMA
                                      Imprensa        PT  EN  ES
──────────────────────────────────────────────────────────────────────
© 2026 Taigo Meireles     Política de Privacidade · Termos de Uso
```
Fundo `#262626`, texto `#FFFFFF`/`#CCC6C3`. Em **M**, colunas empilhadas com 48px entre blocos.

### Outros globais
- `SkipLink` "Ir para o conteúdo" (acessibilidade).
- `ContactFloatingBar` (somente **M**, na página da obra): barra fixa inferior com `[WhatsApp] [Instagram]`, aparece após rolar além da ficha técnica.
- `PageLoader`: linha fina de progresso (2px, `#262626`) no topo durante navegação.
- Página **404** e **erro geral** (ver 3.11).

---

## 3.1 Início

**Objetivo:** impacto imediato com a obra principal, e dois caminhos claros: "Ver obras" e "Contato".

```
D ───────────────────────────────────────────────────────────────────
 TAIGO MEIRELES                Sobre Galeria Disponíveis ... PT|EN|ES
┌───────────────────────────────────────────────────────────────────┐
│                                                                   │
│                 ▣  OBRA PRINCIPAL EM DESTAQUE                     │
│                  (altura 100vh − cabeçalho, sem corte)            │
│                                                                   │
│  TÍTULO DA OBRA, 2025                         [ VER OBRA ]        │
└───────────────────────────────────────────────────────────────────┘

          "MANIFESTO DO ARTISTA EM CINZEL, 40PX,
           CENTRALIZADO, NO MÁXIMO 3 LINHAS"
                                     — Taigo Meireles

 ▣ retrato do artista (5 col)        SOBRE (eyebrow)
                                     Apresentação breve ··· (5 col)
                                     [ Conheça o artista → ]

 OBRAS RECENTES                                  Ver galeria →
 ▣        ▣        ▣        ▣      (4 cards, último ano)
 título   título   título   título
 2025     2025     2024     2024
 ● Disp.  ○ Res.   — Vend.  ● Disp.

 ┌─────────── [ VER OBRAS DISPONÍVEIS ] ───────────┐  (faixa #E7E3E2)

 EXPOSIÇÕES
 ATUAL/FUTURA  ▣ grande (7 col)   Título · Local · datas  [Detalhes]
 PASSADAS      lista de 3 linhas finas: ano  título  local  →

 ▣▣ ATELIÊ  (imagem larga, 12 col, com texto sobreposto em canto)
            "Entre no ateliê"   [ Visitar o ateliê → ]

 INSTAGRAM  @taigomeireles                          Seguir ↗
 ▣ ▣ ▣ ▣ ▣ ▣  (6 quadrados)

 CHAMADA FINAL (fundo #262626)
 "Interessado em uma obra?"   [ WhatsApp ] [ Instagram ] [ Formulário ]
 RODAPÉ
```

- **T:** obras recentes em 2 colunas (4 itens); exposição atual em coluna única; Instagram 3×2.
- **M:** destaque com altura 80vh e imagem `contain` sobre fundo `#E7E3E2` quando a proporção não couber; manifesto 26px; retrato acima do texto; obras recentes em **rolagem horizontal com snap** (é uma lista rolável pelo usuário, não carrossel automático — ver P-09); Instagram 3×2; CTAs empilhados em largura total.
- **Estados:** Instagram indisponível → oculta a seção inteira (não mostra erro ao visitante); sem exposição atual/futura → mostra só passadas; sem obras → oculta seção.
- **Componentes:** `HeroArtwork`, `ManifestoQuote`, `EditorialSplit`, `ArtworkCard`, `CtaBand`, `ExhibitionFeature`, `ExhibitionRow`, `StudioTeaser`, `InstagramGrid`, `ContactCtaSection`.

---

## 3.2 Sobre o artista

Narrativa editorial em capítulos, alternando imagem e texto.

```
D
 SOBRE (eyebrow)
 TAIGO MEIRELES (Cinzel 64)
 Subtítulo: "Pintor · Cidade, País"

 ▣ retrato grande (7 col)            BIOGRAFIA
                                     ··· (4 col, 17/28)

 ── I. TRAJETÓRIA ───────────────────────────────────────────
 Linha do tempo vertical fina:  2012  ···   2016  ···   2024 ···

 ▣ ▣ (duas fotos assimétricas: 5 col + 4 col deslocada 96px)

 ── II. PROCESSO CRIATIVO ───────────────────────────────────
 Citação grande em Cinzel
 ··· texto (6 col centralizadas)
 ▣ foto de processo em largura total

 [ Ver obras ]   [ Visitar o ateliê ]
```

- O conteúdo é montado por **seções configuráveis** (ver tabela `page_sections`): tipos `texto`, `imagem+texto (esq/dir)`, `citação`, `imagem larga`, `par de imagens`, `linha do tempo`.
- **T:** imagens 8 col, texto abaixo; par de imagens lado a lado sem deslocamento.
- **M:** tudo em coluna única; citação 24px; linha do tempo com ano acima do texto.
- Fotos do artista abrem no `Lightbox` (sem bloqueio de download).

---

## 3.3 Galeria

```
D
 GALERIA (Cinzel 48)                           128 obras
 ( Buscar por nome…                    ⌕ )

 Categoria ▾  Técnica ▾  Série ▾  Ano ▾  Disponibilidade ▾   Limpar
 chips ativos: [Óleo ×] [2024 ×]

 ┌────┐ ┌────┐ ┌────┐ ┌────┐
 │ ▣  │ │ ▣  │ │ ▣  │ │ ▣  │   moldura 4:5 neutra, obra contida
 └────┘ └────┘ └────┘ └────┘   sem corte, padding 8%
 Título       Título  ...
 2024 · Óleo sobre tela
 ● Disponível

          [ Carregar mais obras ]   (24 por página)
```

- **Busca por nome:** campo com debounce (300 ms), busca no título do idioma atual; atualiza a URL (`?q=`).
- **Filtros:** dropdowns com múltipla seleção (categoria, técnica, série), ano (lista de anos existentes, múltipla) e disponibilidade (Disponível / Reservada / Vendida). Filtros vivem na query string, então podem ser compartilhados e sobrevivem à troca de idioma.
- **Status no card** (`StatusBadge`): texto sempre presente + marcador: `●` Disponível (preto), `◐` Reservada (contorno com meia-tinta grafite), `○` Vendida (grafite claro). A imagem de obra vendida **não** é esmaecida (a obra continua sendo vitrine do artista).
- **Preço** não aparece no card da galeria (decisão sugerida para manter o tom editorial; aparece na página da obra e em "Disponíveis" — ver P-05).
- **T:** 3 colunas; filtros em uma linha rolável.
- **M:** 2 colunas (gutter 16); botão `[ Filtros (2) ]` abre **painel inferior (bottom sheet)** em tela cheia com os grupos de filtro e `[Ver 37 obras]`.
- **Estados:** carregando → 8 `SkeletonCard`; vazio por filtro → "Nenhuma obra encontrada com esses filtros." `[Limpar filtros]`; erro → "Não foi possível carregar as obras." `[Tentar novamente]`.

---

## 3.4 Obras disponíveis

Mesma estrutura da Galeria, com diferenças:

- Lista **somente** status `disponível` (filtro de disponibilidade não existe aqui).
- Introdução curta editável (ex.: "Obras disponíveis para aquisição. Consulte valores e condições diretamente com o ateliê.").
- Card com **preço** abaixo da técnica quando `exibir preço` estiver ativo e houver valor na moeda do idioma; caso contrário, "Preço sob consulta".
- Cada card tem ações diretas: `[WhatsApp]` `[Instagram]` (botões de ícone com rótulo acessível) além do clique na obra.
- Vazio: "No momento não há obras disponíveis. Fale com o ateliê para conhecer as próximas obras." `[Contato]`.

---

## 3.5 Página da obra

**Sem carrossel.** As imagens aparecem empilhadas (D) ou em grade de miniaturas abaixo da principal (T/M). Cada imagem abre o visualizador em tela cheia com zoom.

```
D
 Galeria / Série Horizonte / Título                (breadcrumb)

 ┌──────────────────────────────────┐   TÍTULO DA OBRA (Cinzel 40)
 │                                  │   2024
 │         ▣ IMAGEM PRINCIPAL       │
 │         (7 col, até 85vh,        │   Óleo sobre tela
 │          clique = tela cheia)    │   120 × 90 cm
 │                                  │   Categoria: Pintura
 └──────────────────────────────────┘   Série: Horizonte
  ▣ detalhe   ▣ verso   ▣ ambiente      ● Disponível
  (miniaturas 3 col, clique = abre       R$ 18.000      (se habilitado)
   em tela cheia naquela imagem)
                                         [ CONSULTAR PELO WHATSAPP ]
                                         [ Consultar pelo Instagram ]
                                         Consultar por e-mail →
                                         (coluna direita sticky, 4 col)

 SOBRE A OBRA
 ··· descrição (6 col)

 VÍDEO
 ▣ fachada do YouTube (16:9) [▶]  — carrega o player só ao clicar

 OUTRAS OBRAS DA SÉRIE                              Ver série →
 ▣ ▣ ▣ ▣
```

- **Visualizador (`ArtworkViewer`)**: fundo `#000`, imagem em versão de zoom (maior largura gerada), zoom com roda/pinça/duplo clique, arrastar para mover, `+`/`−`/`100%`, setas e teclado para a imagem anterior/seguinte, `Esc` fecha, contador "2 / 4". Avanço só por ação do usuário (não é carrossel automático).
- **Download:** não é bloqueado; sem `contextmenu` desabilitado, sem sobreposição transparente.
- **Status:** Reservada → bloco "Esta obra está reservada." + botões "Consultar obras semelhantes" e "Ver disponíveis". Vendida → "Obra vendida — pertence a uma coleção particular." (texto editável) + "Encomendas e obras semelhantes: fale com o ateliê".
- **Preço:** regras em [07 – Interação §7.5](06-interacao-responsividade-estados.md).
- **WhatsApp:** `https://wa.me/{numero}?text={mensagem}`; mensagem modelo editável por idioma no painel, com variáveis `{titulo}`, `{ano}`, `{url}`.
- **Instagram:** `https://ig.me/m/{usuario}` (DM). O Instagram não aceita texto pré-preenchido; o botão copia a referência da obra para a área de transferência e mostra aviso "Referência copiada — cole na mensagem" (sugestão, P-10).
- **T:** imagem em 8 col centralizada; ficha técnica abaixo em 2 colunas (dados | ações).
- **M:** imagem principal em largura total; miniaturas em grade 3 col; ficha; ações em largura total; `ContactFloatingBar` fixa ao rolar.
- **Estados:** carregando → skeleton com proporção da obra (dimensões enviadas pela API); 404 → "Obra não encontrada" + links para Galeria; imagem com falha → moldura `#E7E3E2` com texto alternativo.

---

## 3.6 Exposições

```
D
 EXPOSIÇÕES

 EM CARTAZ                         (só se houver)
 ▣ imagem 7 col     TÍTULO · Local, Cidade
                    12 mar – 30 abr 2026
                    ··· resumo   [ Ver exposição ]

 PRÓXIMAS
 ▣ card  ▣ card  ▣ card   (data em destaque: "A partir de 10 jun 2026")

 ANTERIORES
 2025   Título da exposição           Museu X, São Paulo        →
 2024   Título                         Galeria Y, Lisboa         →
 (lista tipográfica com filtro por ano)
```

- Classificação **calculada pelas datas**: futura (início > hoje), atual (início ≤ hoje ≤ fim), passada (fim < hoje). Exposição sem data de fim = evento de um dia.
- **Página da exposição:** título, local (nome, cidade, país, link), datas, texto, grade de imagens (2 col D / 1 col M, cada uma abre lightbox), vídeo opcional, obras exibidas (opcional, se vinculadas).
- **M:** tudo em coluna única; lista de anteriores com ano acima do título.
- Vazio por bloco: oculta a seção; nenhuma exposição → "Em breve, novas exposições."

---

## 3.7 Ateliê

```
 ATELIÊ
 Texto de abertura (Cinzel 32 + parágrafo)

 Abas de filtro (texto): Todos · O espaço · Processo · Bastidores

 Mosaico editorial: ▣ largo (8 col) + ▣ alto (4 col)
                    ▶ vídeo 16:9 (12 col)
                    ▣ ▣ ▣ (4 col cada)
 Legenda curta abaixo de cada item.
```

- Fotos abrem no lightbox; vídeos do YouTube em fachada clicável (`youtube-nocookie.com`).
- **M:** coluna única; vídeos 16:9 em largura total.
- Paginação "Carregar mais" após 18 itens.

---

## 3.8 Imprensa

```
 IMPRENSA
 Filtros: Todos · Matérias · Entrevistas · Vídeos · Documentos

 2026  ▣ capa   VEÍCULO (eyebrow)
               Título da matéria (Cinzel 24)
               Resumo ··· 
               Ler matéria ↗ | Assistir ▶ | Baixar PDF (2,4 MB) ↓

 KIT DE IMPRENSA (bloco destacado #E7E3E2)
 Biografia curta (PDF) ↓   Fotos do artista em alta (link) ↓
```

- Links externos abrem em nova aba com indicação ↗ e `rel="noopener"`.
- PDFs: mostra tamanho e idioma do documento.
- Vídeos: fachada YouTube inline.
- **M:** capa acima do texto.

---

## 3.9 Contato

```
D
 CONTATO (Cinzel 48)
 Texto curto editável

 FALE DIRETAMENTE                     ENVIE UMA MENSAGEM
 [ WhatsApp  +55 … ]                   Nome*      (                )
 [ Instagram @… ]                      E-mail*    (                )
 contato@…                              Telefone   (                )
 Cidade / ateliê (se houver)            Mensagem*  (                )
                                                    (                )
                                       □ (campo armadilha oculto)
                                       Ao enviar, você concorda com a
                                       Política de Privacidade.
                                       [ ENVIAR MENSAGEM ]
```

- **Validação:** nome (2–120), e-mail válido, telefone opcional (formato livre, 6–30), mensagem (10–5000). Erros exibidos ao sair do campo e no envio, com resumo no topo para leitores de tela.
- **Anti-spam:** campo armadilha (honeypot), tempo mínimo de preenchimento, limite por IP na API e, opcionalmente, desafio invisível de um provedor (P-21).
- **Envio:** botão em estado de carregamento ("Enviando…") e desabilitado.
- **Sucesso:** o formulário é substituído por
  ```
  ✓  MENSAGEM ENVIADA
     Obrigado, {nome}. Recebemos sua mensagem e enviamos uma
     confirmação para {email}. Responderemos em breve.
     [ Ver obras disponíveis ]   Enviar outra mensagem
  ```
  O foco vai para o título de sucesso (anunciado por leitores de tela).
- **Erro:** mensagem acima do botão, conteúdo preservado: "Não foi possível enviar agora. Tente novamente ou fale pelo WhatsApp." `[Tentar novamente]`.
- **Telefone opcional?** O briefing lista o campo, sem dizer se é obrigatório (P-14). Sugestão: opcional.
- **T/M:** canais diretos acima do formulário; campos em largura total.

---

## 3.10 Política de Privacidade e Termos de Uso

Página de texto longo: título Cinzel 48, data de atualização, índice lateral fixo (D) com âncoras, texto em 8 col (máx. 72 caracteres por linha). **M:** índice recolhível no topo.
Conteúdo jurídico a fornecer pelo cliente (P-25). Deve mencionar: formulário de contato, e-mails enviados, métricas anônimas, YouTube e Instagram incorporados, retenção e exclusão de mensagens.

## 3.11 Erro 404 e erro geral

```
 404
 PÁGINA NÃO ENCONTRADA (Cinzel 40)
 O endereço pode ter mudado.
 [ Ir para a galeria ]   Voltar ao início
 ▣ uma obra aleatória disponível (opcional)
```
Erro geral (API fora): "Estamos com uma instabilidade momentânea." `[Tentar novamente]` + links diretos de WhatsApp e Instagram (que não dependem da API — vêm do build ou do cache local).
