# Como continuar o desenvolvimento

Nota de continuidade para quem retoma o projeto (pessoa ou assistente) em outro computador ou numa sessão nova. Atualizada em 27/11/2026, após a etapa 4.

## 1. Preparar o computador

1. Instalar **WSL2 com Ubuntu**, **Docker Desktop** (Settings → Resources → WSL integration → ligar para o Ubuntu), **Git** e **GitHub CLI** (`gh auth login`).
2. Clonar **dentro do WSL** (numa pasta do Windows, como `/mnt/c` ou `/mnt/d`, tudo fica muito lento):
   ```bash
   mkdir -p ~/projetos && cd ~/projetos
   git clone https://github.com/gabriellmeireles/sys_taigo_meireles.git taigo-meireles
   cd taigo-meireles
   ```
3. (Opcional) Copiar a pasta das obras de teste para `dev-data/series/` — ela não está no Git (direitos do artista). Formato: `dev-data/series/<série>/<TÍTULO> - <altura>x<largura>cm - <ano>.jpg`.
4. Rodar:
   ```bash
   bash scripts/setup-dev.sh
   ```
   Cria os `.env`, instala dependências (Composer e npm, dentro dos contêineres), gera a `APP_KEY`, cria os bancos (desenvolvimento e testes), roda migrações e seeders, sobe tudo, importa e traduz as obras de teste (se `dev-data/series` existir) e confere se cada serviço responde.

Endereços: site http://localhost:4200 · painel http://localhost:4201 (login em `backend/database/seeders/DevAdminSeeder.php`) · API http://localhost:8000 · e-mails http://localhost:8025 · banco `localhost:3307`.

Se o push pedir senha no WSL, use o login do GitHub CLI como credencial do repositório:
```bash
git config credential.https://github.com.helper '!gh auth git-credential'
```
(no Windows com `gh` instalado só no Windows: `'!"/mnt/c/Program Files/GitHub CLI/gh.exe" auth git-credential'`).

## 2. Onde estamos

| Etapa | Situação | Commit |
|---|---|---|
| Especificação V1 (`docs/`) e decisões (`docs/09-decisoes-e-pendencias.md`) | Pronta | `17da60f` e seguintes |
| Ambiente Docker no WSL2 | Pronto | `17da60f` |
| 1 — API: login do painel (Sanctum SPA), senha, convites, administradores | Pronta | `1232065` |
| 2 — Design system `@tm/ui` + marca (logo, favicon) | Pronta | `025dc92` |
| 3a — API do catálogo, imagens (Imagick), importador de obras de teste | Pronta | `7d90982` |
| 3b — Painel: estrutura, login, obras, séries/técnicas/categorias | Pronta | `36ebe88` |
| 4 — Traduções automáticas (LibreTranslate), central de traduções, interruptor | Pronta | `2593f30` |
| **5 — Site público: rotas por idioma, Início, Galeria, Disponíveis, página da obra, SEO** | **Próxima** | — |

Documento de especificação também no claude.ai (conta do projeto): "Plataforma Taigo Meireles — Especificação V1" — https://claude.ai/artifact/HnmUrziVYLaM1g9UCwsvHe

## 3. Próximas etapas (ordem combinada)

5. **Site público, parte principal**
   - Rotas `/pt`, `/en`, `/es` com slugs traduzidos; `/` detecta o idioma do navegador (sem PT/EN/ES → inglês); escolha manual lembrada no `localStorage`; seletor PT | EN | ES leva à página equivalente.
   - API pública `/api/v1/public/...` (somente leitura): Início, obras (busca, filtros, disponíveis), obra por slug com slugs dos outros idiomas, filtros.
   - Telas: Início, Galeria, Obras disponíveis, página da obra (sem carrossel; zoom e tela cheia; vídeo YouTube por clique em `youtube-nocookie`).
   - Preço e moeda por idioma: usar `displayPrice` de `@tm/ui` (preço só de disponíveis, P-05).
   - SEO: **meta dinâmica pelo Laravel** (P-16, opção C): o servidor devolve o `index.html` do Angular com título, descrição, Open Graph, canonical e hreflang por rota; `sitemap.xml`.
   - Remover o redirecionamento provisório da raiz para `/design-system`.
6. **Contato**: formulário com anti-spam (honeypot + tempo + limite), caixa de entrada no painel, notificação por e-mail, confirmação automática nos 3 idiomas, configurações de WhatsApp/Instagram; tarefa diária que apaga mensagens com mais de 24 meses (P-20).
7. **Demais conteúdos**: Sobre (seções), Exposições, Ateliê, Imprensa, Privacidade e Termos.
8. **Métricas e dashboard**: coleta sem cookies (hash diário, sem IP), agregação diária, gráficos.
9. **Acabamento e publicação**: sitemap, JSON-LD, acessibilidade, testes em celular/tablet, hospedagem (P-15) e deploy.

## 4. Decisões que valem para o código (resumo)

Detalhes em `docs/09-decisoes-e-pendencias.md`.

- PHP 8.4 · Laravel 13 · Angular 22 · MySQL 8.4 · Node LTS · tudo em Docker no WSL2.
- Série, técnica e categoria obrigatórias (uma de cada); dimensões só em cm (altura × largura × profundidade).
- Preços opcionais, obrigatórios com "exibir preço" ligado; preço só aparece para obras disponíveis.
- Upload até 30 MB (JPG, PNG, TIFF, WebP); original privado; versões 480/960/1600/2400 WebP+JPEG e 1200×630; sem marca d'água.
- Tradução automática: LibreTranslate (trocável); nomes de séries/técnicas/categorias traduzidos à mão.
- Sessão do painel expira após 1 h; tokens de senha 60 min, convite 72 h.
- Mensagens de contato apagadas após 24 meses (`site_settings.message_retention_days = 730`).
- Sem banner de cookies; fontes locais; YouTube só após clique.
- Ainda abertos: hospedagem de produção (P-15) e fornecedores de e-mail, Instagram, geolocalização e anti-spam (P-28).

## 5. Como trabalhamos

- Comandos rodam nos contêineres; atalhos em `scripts/` (`artisan.sh`, `ng.sh`, `test.sh`, `test-frontend.sh`, `restart-frontend.sh`, `smoke-*.sh`). Depois de mudar código de jobs, reinicie o worker: `docker compose restart worker`.
- Testes: backend no MySQL (`taigo_test`, com tradutor falso — nunca chama o LibreTranslate); frontend com Vitest. Rodar ambos antes de cada commit.
- Toda tela é conferida no navegador (desktop e celular) antes de fechar a etapa.
- Um commit por etapa (ou parte), mensagem em português descrevendo o que entrou; push no `main`.
- Textos de interface em português do Brasil; código e comentários em português onde ajuda a entender regras de negócio.
- Mudanças no banco: sempre nova migração (o `schema_v1.sql` é a base histórica).
