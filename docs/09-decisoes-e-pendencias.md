# 10. Decisões assumidas e pontos técnicos ainda não definidos

## 10.1 Decisões assumidas nesta especificação (S)
Cada uma pode ser revertida; foram adotadas para permitir desenhar e modelar.

| # | Suposição |
|---|---|
| S-01 | URLs com prefixo de idioma (`/pt`, `/en`, `/es`) e segmentos/slugs traduzidos; `/` só detecta idioma e redireciona |
| S-02 | Preferência manual de idioma lembrada em `localStorage` (funcional, sem rastreamento; não é cookie) |
| S-03 | PT-BR é o idioma de origem do conteúdo; EN e ES são gerados automaticamente e revisados |
| S-04 | Painel somente em português |
| S-05 | Dimensões em centímetros (altura × largura × profundidade) + texto livre por idioma |
| S-06 | Grid de obras com moldura 4:5 e obra contida (sem corte) |
| S-07 | Preço não aparece no card da Galeria; aparece em "Obras disponíveis" e na página da obra |
| S-08 | Originais ficam privados; o público baixa livremente as versões otimizadas (até 2400px) |
| S-09 | Textos fixos da interface editáveis no painel (`ui_texts`) |
| S-10 | Exposição futura/atual/passada calculada pelas datas |
| S-11 | Respostas às mensagens são feitas fora do sistema (e-mail/WhatsApp do administrador) |
| S-12 | Métricas por coleta própria, sem cookies, com hash diário e sem guardar IP |
| S-13 | Exclusões definitivas (sem lixeira), coerente com ausência de auditoria |
| S-14 | Seções do Início/Sobre podem ser ocultadas (`is_visible`) — isso **não** é fluxo de rascunho; é só exibir/ocultar um bloco já publicado |
| S-15 | YouTube incorporado em modo de privacidade aprimorada (`youtube-nocookie.com`) e só carregado após clique |

## 10.2 Respostas do cliente (26/11/2026)

Registradas no documento compartilhado e refletidas nos demais arquivos.

| # | Resposta |
|---|---|
| P-01 | PHP 8.4; Angular, Laravel e MySQL nas versões estáveis mais recentes e compatíveis (MySQL 8.0.16+ exigido pelo DDL) |
| P-02 | Ícone + texto + borda; cor de erro `#8A2C2C` aprovada como opção |
| P-03 | Site: Cinzel e Open Sans Regular. Painel: Open Sans com os pesos e variações necessários |
| P-04 | Preços opcionais no banco; obrigatórios quando "exibir preço" estiver ligado |
| P-05 | Preço só para obras disponíveis (configurável) |
| P-06 | **Toda obra tem série** (confirmado) → `artworks.series_id` é `NOT NULL` |
| P-07 | Original não fica público; kit de imprensa com arquivos escolhidos |
| P-08 | Sem páginas de série na V1 |
| P-09 | Lista horizontal manual permitida no Início |
| P-10 | Abrir DM do Instagram e copiar a referência da obra |
| P-11 | Exibir o idioma de origem e sinalizar no painel |
| P-12 | Sem selo; nota opcional no rodapé |
| P-13 | i18n em tempo de execução |
| P-14 | Telefone opcional |
| P-15 | Hospedagem de produção não decidida; desenvolvimento em servidor local. Requisitos informados: SSH, Composer e Git; cron; Node.js; SSL; upload 30 MB, memória 1024 MB, 60 s de execução; e-mail `contato@taigomeireles.com`; backup da hospedagem |
| P-16 | Meta dinâmica pelo Laravel (opção C) |
| P-17 | `America/Sao_Paulo` |
| P-18 | Soma dos únicos diários, rotulada |
| P-19 | Base local gratuita de geolocalização |
| P-20 | **24 meses**: mensagens mais antigas são excluídas automaticamente (tarefa diária); os administradores também podem excluir manualmente a qualquer momento. Padrão `site_settings.message_retention_days = 730` |
| P-21 | Começar sem fornecedor de anti-spam |
| P-22 | Cópia local só se os termos da Meta permitirem |
| P-23 | Dimensões só em centímetros |
| P-24 | Uma categoria e uma técnica por obra |
| P-25 | Textos jurídicos fornecidos por advogado |
| P-26 | Sem banner de cookies |
| P-27 | `www`, `admin.`, `api.` |
| P-28 | **Tradução: LibreTranslate** auto-hospedado (código aberto, sem custo), atrás da interface `TranslationProvider` para poder trocar depois. Nomes de categorias, técnicas e séries são traduzidos à mão (sem glossário no LibreTranslate). E-mail, Instagram, geolocalização e anti-spam: **decidir depois** |
| P-29 | CDN no futuro |
| P-30 | JPG, PNG, TIFF, WebP até 30 MB por arquivo |
| P-31 | Ordem manual, ano decrescente como reserva |
| P-32 | Sessão expira após 1 h de inatividade |

## 10.3 Questões originais e recomendações (histórico)

| # | Questão | Por que importa | Recomendação |
|---|---|---|---|
| P-01 | Versões de Angular, Laravel, PHP e MySQL | Compatibilidade entre si e com o plano da Hostinger | Usar as versões estáveis com suporte ativo na data de início; confirmar PHP e MySQL disponíveis no plano |
| P-02 | Paleta 100% neutra x estados de erro/sucesso | Erro só em preto/cinza é menos evidente | Comunicar por ícone + texto + borda; opcionalmente aprovar 1 cor funcional de erro (`#8A2C2C`) |
| P-03 | "Open Sans Regular" para toda a interface | Dashboard e tabelas se beneficiam de peso maior | Manter Regular; se necessário, liberar Open Sans 600 só no painel |
| P-04 | Preços R$ e US$ são obrigatórios? | Obras vendidas antigas podem não ter preço | Opcionais no banco; obrigatórios no painel apenas quando "exibir preço" estiver ligado |
| P-05 | Exibir preço de obra reservada/vendida com a opção ligada? | Requisito diz "conforme opção da obra"; mercado de arte costuma ocultar preço de vendidas | Mostrar só para disponíveis (configurável em `site_settings.hide_price_unless_available`) |
| P-06 | Toda obra tem série? | Briefing lista "série" como campo da obra | Série opcional ("Sem série" não aparece no filtro) |
| P-07 | Disponibilizar o arquivo original em alta ao público (imprensa)? | "Não impedir download" x peso/metadados do original | Não expor o original por padrão; oferecer kit de imprensa com arquivos selecionados |
| P-08 | Páginas públicas por série? | SEO e navegação por série | Não na V1 (série é filtro); modelo já suporta slug/SEO de série para o futuro |
| P-09 | Lista horizontal de obras recentes no celular conta como "carrossel"? | Proibição de carrossel está na página da obra | Permitido no Início (rolagem manual, sem autoplay); se não desejar, usar grade 2 col |
| P-10 | Instagram não aceita mensagem pré-preenchida | "Iniciar consulta pelo Instagram" | Abrir DM (`ig.me`) e copiar referência da obra para colar |
| P-11 | O que exibir se a tradução automática falhar ou não existir? | Requisito: mostrar automática até revisão | Exibir texto do idioma de origem como último recurso e sinalizar no painel |
| P-12 | Indicar ao visitante que o texto é tradução automática? | Transparência x estética | Não exibir selo (opcional: nota discreta no rodapé "Algumas traduções são automáticas") |
| P-13 | i18n da interface Angular: em tempo de execução x build por idioma | Troca de idioma sem recarregar; textos editáveis | Tempo de execução |
| P-14 | Telefone obrigatório no formulário? | Briefing lista o campo sem obrigatoriedade | Opcional |
| P-15 | Plano da Hostinger (compartilhado/cloud x VPS) e recursos (Node, SSH, cron, PHP, extensões, limites, e-mail, backup) | Define SSR, filas, processamento de imagem e deploy | Levantar o plano contratado antes do início; VPS se SSR for requisito |
| P-16 | SSR x prerender x meta dinâmica | Prévias no WhatsApp/redes exigem meta no HTML | SSR se houver Node estável; senão meta dinâmica via Laravel |
| P-17 | Fuso horário do dashboard | "Hoje" e cortes diários | `America/Sao_Paulo`, configurável |
| P-18 | Definição de "visitante único" em períodos de vários dias | Sem cookies/ID persistente, só há únicos por dia | Somar únicos diários e rotular; ID persistente só com avaliação jurídica |
| P-19 | Fonte de geolocalização por país | Métrica "países" depende dela | Base local gratuita com atualização mensal |
| P-20 | Retenção de mensagens de contato | LGPD (minimização) | Definir prazo (ex.: 24 meses) ou manter até exclusão manual |
| P-21 | Anti-spam com fornecedor externo? | Privacidade x eficácia | Começar sem fornecedor (honeypot + tempo + rate limit) |
| P-22 | Guardar cópia local das imagens do Instagram | Termos da Meta e URLs que expiram | Usar URLs da API com cache curto; cópia local só se permitido |
| P-23 | Exibir dimensões em polegadas para EN | Público americano | Mostrar "120 × 90 cm (47.2 × 35.4 in)" em EN |
| P-24 | Obra com mais de uma técnica/categoria | Modelo assume uma de cada | Manter 1:1; técnica mista vira técnica própria ("Técnica mista sobre tela") |
| P-25 | Textos jurídicos (Privacidade, Termos) | Conteúdo jurídico | Fornecidos por advogado; devem citar formulário, e-mails, métricas sem cookies, YouTube, Instagram |
| P-26 | Ausência de banner de cookies | Só é sustentável se nada não essencial gravar no navegador | Métricas sem cookies, YouTube após clique, fontes locais, sem widgets de terceiros — validar com jurídico |
| P-27 | Domínio definitivo e subdomínios | Configuração de CORS, cookies de sessão, SSL, e-mail | `www`/raiz para site, `admin.` e `api.` |
| P-28 | Provedores de e-mail, tradução, Instagram, geolocalização, anti-spam | Nenhum foi escolhido | Ver tabela em [Arquitetura §6.11](05-arquitetura.md) |
| P-29 | CDN | Não é requisito da V1 | Registrada como possibilidade futura; `MEDIA_BASE_URL` configurável permite adotar depois |
| P-30 | Formatos e tamanho máximo de upload (TIFF? limite em MB?) | Memória do servidor para processar | Aceitar JPG, PNG, TIFF, WebP até 50 MB, se o plano permitir |
| P-31 | Ordem padrão da galeria | Curadoria x cronologia | Ordem manual com fallback por ano desc. |
| P-32 | Sessão do painel: tempo de expiração | Segurança x conveniência | 8 h de inatividade |

## 10.4 Fora do escopo (registrado para não ser reintroduzido)
Checkout, pagamento online, pedidos, contas/área de colecionadores, newsletter e campanhas, banner de cookies, 2FA, histórico de auditoria, rascunho e prévia, CDN obrigatória.

## 10.5 Próximos passos sugeridos
1. Escolher a hospedagem de produção (P-15), com PHP 8.4 e MySQL 8.0.16 ou superior.
2. Escolher o fornecedor de envio de e-mail (P-28) antes da publicação.
3. Desenvolver a V1 a partir da base da API e do login do painel (ambiente Docker pronto; banco validado no MySQL 8.4).
4. Opcional: protótipo visual de alta fidelidade das telas-chave (Início, Galeria, Obra, Contato, Dashboard, Editor de obra).
