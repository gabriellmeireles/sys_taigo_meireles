# 7. Regras de interação, responsividade e estados de interface

## 7.1 Navegação
- Cabeçalho fixo e compacto ao rolar para baixo; reaparece ao rolar para cima (M/T). Em D permanece fixo compacto.
- Rota ativa indicada por sublinhado 1px.
- Ao navegar, a rolagem volta ao topo; ao voltar no histórico, restaura a posição anterior (galeria inclusive, com a página de resultados já carregada).
- Título do documento atualizado a cada rota (`{Título SEO} — Taigo Meireles`); foco movido para o `h1` da nova página (acessibilidade em SPA).

## 7.2 Galeria, busca e filtros
- Busca com debounce de 300 ms, mínimo 2 caracteres, ignora acentos e caixa ("mare" encontra "Maré").
- Filtros combinam: **E** entre grupos, **OU** dentro do grupo.
- Contagem de resultados atualizada ao vivo (`aria-live`).
- "Carregar mais" (24 itens) em vez de rolagem infinita — preserva o rodapé e permite chegar aos links legais.
- Filtros e página atual na URL: `?q=mar&tecnica=oleo,acrilica&ano=2024&status=disponivel`.

## 7.3 Visualizador de obra
| Entrada | Ação |
|---|---|
| Clique/toque na imagem | Abre em tela cheia naquela imagem |
| Roda do mouse / pinça / duplo clique / `+` `−` | Zoom (1× a 4×, limitado à resolução real) |
| Arrastar | Move a imagem ampliada |
| `←` `→` / deslizar (sem zoom) / botões | Imagem anterior/seguinte |
| `Esc` / botão Fechar / deslizar para baixo (M) | Fecha e devolve o foco à miniatura de origem |
| `0` | Volta para 100% ajustado |

O visualizador carrega primeiro a versão "large" e, ao aplicar zoom, a versão "zoom" (maior). Foco preso dentro do diálogo enquanto aberto.

## 7.4 Contato
- WhatsApp: abre `wa.me` em nova aba (D) ou o app (M). Registra evento `whatsapp_click` com a obra (se houver) **antes** da navegação, via `sendBeacon`.
- Instagram: abre `ig.me/m/{usuario}`; registra `instagram_click`; copia referência (P-10).
- Formulário: envio único (botão desabilitado), tempo limite de 15 s com mensagem de erro e opção de nova tentativa; nunca apaga o que foi digitado em caso de erro.

## 7.5 Regras de preço (derivadas dos requisitos)
Exibir preço quando **todas** forem verdadeiras:
1. `show_price = verdadeiro` na obra;
2. Existe valor na moeda do idioma (PT → `price_brl`; EN/ES → `price_usd`);
3. Status = `disponível` (P-05, decidido). Para reservada/vendida, o preço fica oculto mesmo com a opção ligada.

Caso contrário, mostrar "Preço sob consulta" (somente em obras disponíveis). Formatação: `pt-BR` → `R$ 18.000`; `en` → `US$ 3,500`; `es` → `US$ 3.500`. Centavos omitidos quando zerados.
Não há conversão automática entre moedas: os dois valores são digitados manualmente (requisito).

## 7.6 Responsividade — resumo por tela
| Tela | M (<768) | T (768–1279) | D (≥1280) |
|---|---|---|---|
| Cabeçalho | Assinatura + "Menu" (overlay) | Igual a M até 1023; nav completa a partir de 1024 | Nav completa |
| Início | Coluna única, obras recentes em lista horizontal com snap | 2 col | Composição editorial 12 col |
| Galeria | 2 col, filtros em bottom sheet | 3 col, filtros em linha rolável | 4 col, filtros em linha |
| Obra | Imagem total, miniaturas 3 col, barra de contato fixa | Imagem 8 col, ficha abaixo | Imagem 7 col + ficha sticky 4 col |
| Exposições | Lista única | 2 col | Destaque + grade 3 col |
| Contato | Canais acima, form largura total | Canais acima, form 6 col | 2 colunas |
| Painel | Gaveta lateral, tabelas → cartões, ações fixas no rodapé | Sidebar em ícones | Sidebar completa |

Alvos de toque ≥ 44×44px. Nada depende de hover para funcionar. Orientação paisagem em celular: visualizador ocupa a tela inteira.

## 7.7 Estados de interface (matriz)
| Estado | Site público | Painel |
|---|---|---|
| **Carregando** | Skeleton com a forma do conteúdo (cards 4:5, linhas de texto); linha de progresso no topo entre rotas. Nunca tela branca. | Skeleton de tabela/cards; botões com "Salvando…" |
| **Vazio** | Mensagem curta em Cinzel + texto + uma ação (ex.: Limpar filtros / Contato). Seções opcionais vazias são **ocultadas** no Início. | Mensagem + ação primária de criar |
| **Erro** | Mensagem humana sem jargão + "Tentar novamente" + canais diretos (WhatsApp/Instagram). 404 dedicado. | Banner/toast com detalhe; erros de campo vindos da API (422) mapeados para cada campo |
| **Sucesso** | Formulário substituído por confirmação com nome e e-mail; foco no título | Toast "Salvo e publicado" |
| **Parcial** | Imagem que falha → moldura com texto alternativo; Instagram que falha → seção oculta; vídeo indisponível → link "Assistir no YouTube" | Upload com falha em 1 de N arquivos → só esse marcado com erro |
| **Offline** | Aviso discreto "Você está sem conexão" | Banner persistente; bloqueia salvar |

## 7.8 Acessibilidade (meta: WCAG 2.2 AA)
- HTML semântico, `lang` correto por página (`pt-BR`, `en`, `es`), `hreflang` nos links do seletor.
- Texto alternativo por imagem e por idioma (obrigatório no painel para a imagem principal; sugerido para as demais).
- Status nunca só por cor; contraste conforme §5.1.
- Visualizador, menus e diálogos operáveis por teclado, com foco preso e retorno de foco.
- Vídeos: fachada com rótulo "Reproduzir vídeo: {título}".
- Mensagens de formulário e contagens com `aria-live`.
- `prefers-reduced-motion` respeitado.
