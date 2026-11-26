# 4. Inventário de telas do painel administrativo

**Idioma do painel:** português (suposição S-04). **Público:** administradores com permissões idênticas.
**Princípio:** salvar publica. Toda ação destrutiva pede confirmação; toda gravação mostra retorno visível.

## 4.0 Estrutura geral (`AdminShell`)

```
D ≥1280
┌──────────────┬──────────────────────────────────────────────────────┐
│ TAIGO        │  Obras / Editar obra                Ver no site ↗  ☺ │  topbar 64px
│ MEIRELES     ├──────────────────────────────────────────────────────┤
│ painel       │                                                      │
│              │   conteúdo (máx. 1200px, margens 40px)               │
│ Dashboard    │                                                      │
│ Mensagens (3)│                                                      │
│ ─ CONTEÚDO   │                                                      │
│ Obras        │                                                      │
│ Categorias…  │                                                      │
│ Páginas      │                                                      │
│ Exposições   │                                                      │
│ Ateliê       │                                                      │
│ Imprensa     │                                                      │
│ Mídia        │                                                      │
│ Vídeos       │                                                      │
│ ─ IDIOMAS    │                                                      │
│ Traduções    │                                                      │
│ SEO          │                                                      │
│ ─ AJUSTES    │                                                      │
│ Contato      │                                                      │
│ Respostas    │                                                      │
│  automáticas │                                                      │
│ Site         │                                                      │
│ Integrações  │                                                      │
│ Administrad. │                                                      │
└──────────────┴──────────────────────────────────────────────────────┘
 sidebar 248px, fundo #262626, texto #CCC6C3, item ativo branco + barra 2px
```

- **T (768–1279):** sidebar recolhida em ícones (72px) com rótulos em tooltip; expansível.
- **M (<768):** topbar com botão "Menu" que abre gaveta lateral; tabelas viram listas de cartões; editores em coluna única com barra de ações fixa no rodapé. Uso em celular é suportado para tarefas rápidas (mudar status, ler mensagens, trocar link de contato).
- Menu do usuário (☺): Minha conta, Sair.
- Contador de mensagens não lidas no item "Mensagens".

### Padrões de interação do painel
- **Barra de ações do editor** (sticky no rodapé em todos os tamanhos): `Cancelar` · `[ Salvar ]`. Indicador "Alterações não salvas" e alerta ao sair da página com alterações pendentes.
- **Feedback:** `Toast` no canto inferior ("Obra salva e publicada."), erros de validação no campo + resumo no topo.
- **Confirmação:** `ConfirmDialog` para excluir ("Excluir 'Título'? Esta ação não pode ser desfeita.").
- **Tabelas:** busca, filtros, ordenação, paginação (25/50/100), seleção múltipla para ações em lote quando fizer sentido.

---

## 4.1 Login

```
           TAIGO MEIRELES
           Painel administrativo

           E-mail  (                     )
           Senha   (                  👁 )
           [ ENTRAR ]
           Esqueci minha senha
```
- Centralizado, card 400px, fundo `#E7E3E2` à esquerda com obra em destaque (D) / só formulário (M).
- Erro genérico: "E-mail ou senha incorretos." (não revela qual).
- Bloqueio temporário após tentativas: "Muitas tentativas. Tente novamente em X minutos."
- Estado de carregamento no botão.

## 4.2 Esqueci minha senha / Redefinir senha
- **Solicitar:** campo e-mail → sempre responde "Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha." (não revela existência).
- **Redefinir** (link do e-mail): nova senha + confirmação + medidor de força simples; regras: mínimo 12 caracteres (sugestão). Token expirado → "Este link expirou. Solicite um novo." `[Solicitar novo link]`.
- **Sucesso:** "Senha alterada. Entre com a nova senha." → Login.

## 4.3 Dashboard

```
 DASHBOARD                         [Hoje][7 dias][30 dias][Personalizado ▾]
                                                   01/11/2026 – 26/11/2026
┌──────────┬──────────┬──────────┬──────────┬──────────┐
│ ACESSOS  │ VISIT.   │ MENSAG.  │ WHATSAPP │ INSTAGRAM│   KPI tiles
│ 12.480   │ ÚNICOS   │ 38       │ 214      │ 97       │   valor + variação
│ ▲ 12%    │ 4.902    │ ▲ 5      │ ▼ 3%     │ ▲ 20%    │   vs. período anterior
└──────────┴──────────┴──────────┴──────────┴──────────┘

 ACESSOS AO LONGO DO TEMPO                        ── acessos  ┄┄ únicos
 gráfico de linhas (dia; hora quando "Hoje")

┌──────────────────────────────┬──────────────────────────────┐
│ OBRAS MAIS ACESSADAS         │ ORIGEM DOS ACESSOS           │
│ ▣ Título         1.204 ████  │ Instagram     41% ████████   │
│ ▣ Título           880 ███   │ Busca         22% ████       │
│ ▣ Título           610 ██    │ Direto        19% ████       │
│ … (top 10, link p/ editar)   │ Outros sites  12% ██         │
│                              │ WhatsApp       6% █          │
├──────────────────────────────┼──────────────────────────────┤
│ PAÍSES                       │ IDIOMAS                      │
│ Brasil       62% ████████    │ Idioma do site: PT 58 EN 30  │
│ EUA          14% ██          │                 ES 12        │
│ Portugal      8% █           │ Idioma do navegador: barras  │
│ … top 10                     │                              │
└──────────────────────────────┴──────────────────────────────┘

 CLIQUES DE CONTATO POR OBRA (tabela: obra · WhatsApp · Instagram · e-mail)
```

**Proposta de visualização**
- **KPI tiles:** número grande em Open Sans 32, rótulo em caixa-alta 12, variação vs. período anterior de mesma duração (▲/▼ com texto, não só cor).
- **Série temporal:** linhas para acessos (sólida, `#262626`) e visitantes únicos (tracejada, `#6F6D6E`); granularidade por hora em "Hoje", por dia nos demais; tooltip com valores exatos.
- **Rankings** (obras, origem, países, idiomas): barras horizontais com rótulo e valor à direita (mais legíveis que pizza/rosca). Top 10 + "Ver todos".
- **Filtro de período:** segmentado `Hoje · 7 dias · 30 dias · Personalizado` (seletor de intervalo com calendário duplo). Fuso horário do site configurável (padrão `America/Sao_Paulo`, P-17).
- **Estados:** carregando → skeleton por card; sem dados → "Ainda não há dados para este período."; métrica dependente de integração não configurada (ex.: país sem base de geolocalização) → card com aviso "Requer configuração de geolocalização" e link para Integrações.
- Nota no rodapé do dashboard: "Visitantes únicos são contados por dia, de forma anônima" (ver arquitetura §6.10 e P-18).

## 4.4 Mensagens (caixa de entrada)

```
 MENSAGENS              (Buscar…)  [Todas|Não lidas|Lidas]   Selecionar ▾
┌──────────────────────────────────┬──────────────────────────────────┐
│ ● Maria Souza          14:32     │ Maria Souza                      │
│   Interesse em "Horizonte II"    │ maria@… · +55 11 …  · PT         │
│   Olá, gostaria de saber…        │ 26/11/2026 14:32                 │
│ ─────────────────────────────    │ Obra: Horizonte II ↗             │
│   John Smith           ontem     │ Página de origem: /pt/obras/…    │
│   Hello, I saw your work…        │                                  │
│ …                                │ Mensagem completa ···            │
│                                  │                                  │
│                                  │ [Responder por e-mail] [WhatsApp]│
│                                  │ Marcar como não lida · Excluir   │
│                                  │ Status: notificação enviada ✓    │
│                                  │         confirmação enviada ✓    │
└──────────────────────────────────┴──────────────────────────────────┘
```
- Lista + painel de leitura (D); lista → detalhe (T/M).
- Abrir marca como lida automaticamente.
- Ações em lote: marcar lidas/não lidas, excluir.
- "Responder por e-mail" abre `mailto:` com assunto "Re: …" (respostas são feitas fora do sistema — não há envio de e-mail livre pelo painel nesta versão, S-11).
- Mostra status de envio da notificação e da confirmação automática; falha → `[Reenviar]`.
- Vazio: "Nenhuma mensagem por aqui."

## 4.5 Obras — lista

```
 OBRAS  (128)                                          [ + NOVA OBRA ]
 (Buscar por título…)  Categoria ▾ Técnica ▾ Série ▾ Ano ▾ Status ▾
 Visualizar: [Tabela][Grade]

 □  ▣  Título              Ano   Série      Status        Preço   Trad.
 □  ▣  Horizonte II        2024  Horizonte  [Disponível▾] R$/US$  EN✓ ES◌
 □  ▣  Maré                2023  —          [Vendida   ▾] oculto  EN◌ ES◌
```
- Status editável na própria linha (dropdown) com toast de confirmação.
- Coluna "Trad.": `✓` revisada manualmente, `◌` automática, `!` desatualizada, `—` ausente.
- Ordenação manual da galeria: modo "Ordenar" com arrastar e soltar (sugestão; padrão é ano desc. + ordem manual).
- Ações em lote: alterar status, exibir/ocultar preço, excluir.
- Grade: miniaturas grandes com status — útil para curadoria visual.

## 4.6 Obras — editor

Página única com seções âncora (não abas que escondem erros):

```
 ← Obras    HORIZONTE II                         Ver no site ↗
 Idioma de edição: [PT (origem)] [EN ◌ auto] [ES ✓ revisado]

 1 DADOS
   Título* (     )        Ano* (    )
   Largura (   ) × Altura (   ) × Profundidade (   ) cm
   Texto livre de dimensões (opcional, ex.: "díptico, 2× 60 × 80 cm")
   Técnica* [Óleo sobre tela ▾] (+ nova)
   Categoria* [Pintura ▾]   Série [Horizonte ▾] (+ nova)
   Descrição (editor de texto simples: parágrafos, negrito, itálico, link)
   □ Destacar no Início (obra principal)   Tom da imagem: (clara|escura)

 2 IMAGENS
   ┌ Arraste imagens aqui ou [Selecionar arquivos] — JPG, PNG, TIFF, WebP até 30 MB
   ▣★ ▣ ▣ ▣     arrastar para reordenar · ★ = principal · ⋯ (texto alternativo,
                substituir arquivo, baixar original, remover)
   Progresso por arquivo · "Gerando versão otimizada…" · erro com [Tentar de novo]

 3 VÍDEO
   URL do YouTube (                         ) → pré-visualização da miniatura
   Validação: precisa ser um link do YouTube válido

 4 STATUS E PREÇO
   Status*  (•) Disponível ( ) Reservada ( ) Vendida
   Preço em reais  R$ (        )
   Preço em dólares US$ (        )
   [■] Exibir preço no site
   Aviso: "Preço não será exibido em EN/ES porque o valor em US$ está vazio."

 5 SEO E COMPARTILHAMENTO  (por idioma selecionado)
   Slug  /pt/obras/( horizonte-ii )
   Título SEO (     ) 38/60      Descrição (     ) 120/160
   Título social / Descrição social / Imagem social (padrão: imagem principal)
   Pré-visualização: resultado de busca + cartão de compartilhamento

 [Excluir obra]                                     Cancelar  [ SALVAR ]
```

- **Idioma de edição:** no idioma de origem edita-se tudo; em EN/ES apenas campos traduzíveis (título, descrição, dimensões em texto livre, slug, SEO), com o texto de origem visível ao lado para referência (`TranslationField` lado a lado em D, empilhado em M).
- Salvar em PT com mudança de texto → traduções **automáticas** são regeneradas; traduções **manuais** são preservadas e marcadas como **desatualizadas** (aviso amarelado-neutro + botão "Retraduzir automaticamente").
- Botão por campo em EN/ES: "Restaurar tradução automática".
- Imagens: a ordem e a principal são salvas na hora (com toast) para não perder upload longo.
- Validação: ao menos 1 imagem; título, ano, técnica, categoria, status obrigatórios. Preços: ver P-04.

## 4.7 Categorias, Técnicas e Séries
Três telas idênticas (`TaxonomyManager`): lista ordenável com nome por idioma, slug, nº de obras; edição em painel lateral com campos PT/EN/ES e status de tradução. Série tem também descrição, imagem de capa e SEO (páginas de série opcionais — P-08). Excluir bloqueado se houver obras vinculadas ("12 obras usam esta técnica. Reatribua antes de excluir.").

## 4.8 Páginas
Lista das páginas fixas: Início, Sobre, Galeria, Obras disponíveis, Exposições, Ateliê, Imprensa, Contato, Política de Privacidade, Termos de Uso.
- **Editor de página:** título, introdução, SEO por idioma e **seções** (quando o tipo de página permite: Início e Sobre) — lista ordenável de blocos (`texto`, `imagem + texto`, `citação/manifesto`, `imagem larga`, `par de imagens`, `linha do tempo`), cada um com seus campos traduzíveis.
- **Início:** além dos blocos, configura obra em destaque, manifesto, quantidade de obras recentes, exibir/ocultar Instagram, textos das chamadas.
- Política/Termos: editor de texto longo com títulos (H2/H3) e data de atualização.

## 4.9 Exposições
Lista com abas `Atuais · Futuras · Passadas` (calculadas por data). Editor: título*, local (nome, endereço/cidade, país, link), data início*, data fim, descrição, imagem de capa, galeria de imagens (upload, ordenar), vídeo YouTube opcional, obras exibidas (busca e seleção, opcional), SEO por idioma.

## 4.10 Ateliê
Grade ordenável de itens (foto ou vídeo) com filtro por tema (Espaço, Processo, Bastidores). Adicionar foto (upload múltiplo) ou vídeo (URL YouTube). Cada item: legenda traduzível, tema, ordem. Texto de abertura do Ateliê fica em Páginas.

## 4.11 Imprensa
Lista por data. Editor: tipo (matéria, entrevista, vídeo, documento PDF, link), veículo, data de publicação, título e resumo (traduzíveis), link externo, vídeo YouTube, upload de PDF (mostra nome, tamanho, idioma do documento), capa, destaque no kit de imprensa.

## 4.12 Biblioteca de mídia
Grade de todas as imagens e documentos com busca, filtro (imagem/PDF, onde é usada, sem texto alternativo). Detalhe: pré-visualização, metadados (dimensões, peso original e otimizado, data), texto alternativo e legenda por idioma, "Usado em: Obra X, Exposição Y", baixar original, substituir, excluir (bloqueado se em uso). Estado de processamento: `Processando` / `Pronto` / `Falhou [Reprocessar]`.

## 4.13 Vídeos
Lista dos vídeos do YouTube cadastrados (miniatura, título por idioma, onde é usado). Adicionar por URL; validação do ID; título e descrição traduzíveis (usados em SEO/legenda).

## 4.14 Traduções
Central de revisão:
```
 TRADUÇÕES                     Idioma: [EN][ES]   Status: [Todas|Automáticas|Revisadas|Desatualizadas|Ausentes]
 Tipo ▾ (Obra, Série, Página, Exposição, Ateliê, Imprensa, Mídia, Textos do site)

 Tipo      Item              Campo        Status          Atualizado
 Obra      Horizonte II      título…      ◌ Automática    hoje        [Revisar]
 Página    Sobre             seção 2      ! Desatualizada 3 dias      [Revisar]
```
- Editor lado a lado: origem (somente leitura) | tradução (editável). `[Salvar como revisada]`, `[Gerar automática]`.
- Progresso por idioma: "EN: 82% revisado".
- Textos fixos da interface do site (rótulos de botões, mensagens de erro) também editáveis aqui como "Textos do site" (sugestão, S-09).
- Se o provedor de tradução automática não estiver configurado ou falhar: status "Ausente" e o site exibe o texto de origem como último recurso (P-11).

## 4.15 SEO
Visão geral por idioma: lista de todas as páginas públicas com título SEO, descrição, slug e alerta de problemas (vazio, longo demais, duplicado). Edição rápida em linha. Configurações gerais: título padrão do site, sufixo, descrição padrão, imagem social padrão, perfis sociais (para dados estruturados), indexação (bloquear em homologação).

## 4.16 Configurações → Contato
- WhatsApp: número em formato internacional (validação E.164) + mensagem pré-preenchida por idioma com variáveis `{titulo}`, `{ano}`, `{url}` (para obra) e mensagem geral (sem obra).
- Instagram: usuário (`@`) e URL do perfil.
- E-mail público exibido no site (opcional), telefone público (opcional), cidade/endereço do ateliê (opcional).
- **E-mail destinatário do formulário** (único) + botão `[Enviar e-mail de teste]`.

## 4.17 Configurações → Respostas automáticas
Abas PT | EN | ES: assunto e corpo (texto com variáveis `{nome}`, `{mensagem}`, `{site}`), ativar/desativar por idioma, pré-visualização renderizada e `[Enviar teste para mim]`. Textos editados manualmente (não usam tradução automática).

## 4.18 Configurações → Site
Nome do site, idioma de origem do conteúdo (padrão PT), idioma de fallback (fixo EN pela regra de negócio), fuso horário, textos do rodapé, textos de status (vendida/reservada), redes sociais, retenção de mensagens e de eventos de métricas (sugestão).

## 4.19 Configurações → Integrações
Cartões para cada integração **ainda não escolhida** (ver arquitetura §6.11), cada um com status `Não configurado / Ativo / Com erro`, data da última sincronização e ações (`Conectar`, `Testar`, `Sincronizar agora`): envio de e-mail, tradução automática, feed do Instagram, geolocalização de país, proteção anti-spam. Chaves e segredos **não** são editados no painel: ficam nas variáveis de ambiente do servidor (sugestão de segurança); o painel mostra apenas o status.

## 4.20 Administradores
Lista (nome, e-mail, último acesso, ativo/inativo). Ações: convidar novo (nome + e-mail → recebe link para definir senha), desativar/reativar, excluir. Regras: não é possível desativar a si mesmo nem o último administrador ativo. Todos têm as mesmas permissões.

## 4.21 Minha conta
Nome, e-mail, alterar senha (senha atual + nova), encerrar outras sessões (sugestão).

## 4.22 Estados do painel
| Estado | Tratamento |
|---|---|
| Carregando lista | Skeleton de linhas |
| Lista vazia | Ilustração tipográfica + ação primária ("Cadastrar a primeira obra") |
| Erro de rede | Banner no topo "Sem conexão com o servidor" + `[Tentar novamente]`; dados do formulário preservados |
| Sessão expirada | Modal "Sua sessão expirou" → login e retorno à mesma página; formulário preservado em memória |
| Validação | Campo com borda `#000` 2px + ícone + texto do erro; resumo clicável no topo |
| Sucesso | Toast 4 s, com `aria-live="polite"` |
| Upload | Barra por arquivo, cancelar, erro por arquivo sem perder os demais |
