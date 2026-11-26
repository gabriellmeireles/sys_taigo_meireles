# Plataforma digital — Taigo Meireles

Site público trilíngue + painel administrativo (Angular) + API REST (Laravel) + MySQL.

## Estrutura

| Pasta | Conteúdo |
|---|---|
| `backend/` | API Laravel 13 (PHP 8.4). Modelo de dados em `database/sql/schema_v1.sql`, aplicado pela migração `2026_11_26_000000_create_platform_schema` |
| `frontend/` | Workspace Angular 22: apps `site` e `admin`, biblioteca `ui` (design system) |
| `docker/` | Imagens de desenvolvimento (PHP-FPM, nginx, Node LTS) |
| `scripts/` | Utilitários do ambiente e testes rápidos |
| `docs/` | Especificação da V1 |

## Ambiente de desenvolvimento (Docker no WSL2)

Pré-requisitos: Docker Desktop com integração WSL2 ligada para a distribuição Ubuntu, e o projeto dentro do sistema de arquivos do WSL (ex.: `~/projetos/taigo-meireles`).

```bash
cp .env.example .env                 # portas e credenciais do compose (só na primeira vez)
docker compose up -d --build         # sobe tudo
docker compose run --rm api php artisan migrate --seed   # cria as tabelas (primeira vez)
```

| Serviço | Endereço |
|---|---|
| Site público (`ng serve site`) | http://localhost:4200 |
| Painel (`ng serve admin`) | http://localhost:4201 |
| API Laravel (nginx + PHP-FPM) | http://localhost:8000 (saúde: `/up`) |
| Mailpit (e-mails capturados) | http://localhost:8025 |
| MySQL 8.4 | `localhost:3307` (usuário/senha/banco em `.env`) |
| LibreTranslate (tradução automática, só PT/EN/ES) | http://localhost:5000 (a primeira subida baixa os modelos) |

Administrador de teste (só em `local`): ver `backend/database/seeders/DevAdminSeeder.php`.

Comandos úteis:

```bash
docker compose run --rm api php artisan <comando>        # artisan / composer
docker compose run --rm node npm install <pacote>        # npm / ng no workspace Angular
docker compose logs -f worker                            # fila e agendador
bash scripts/migrate-fresh.sh                            # recria o banco local do zero (apaga os dados)
bash scripts/smoke-db.sh                                 # testa as regras do banco e o rollback
bash scripts/smoke-up.sh                                 # sobe tudo e confere se cada serviço responde
```

Limites do PHP no ambiente: upload 30 MB, memória 1024 MB, 60 s (`docker/php/php.ini`).

## Especificação (na ordem pedida)

| # | Seção | Arquivo |
|---|---|---|
| 1–2 | Conceito do produto · Mapa do site e fluxos | [docs/01-conceito-mapa-e-fluxos.md](docs/01-conceito-mapa-e-fluxos.md) |
| 3 | Telas públicas | [docs/02-telas-publicas.md](docs/02-telas-publicas.md) |
| 4 | Telas do painel administrativo | [docs/03-telas-painel-administrativo.md](docs/03-telas-painel-administrativo.md) |
| 5 | Design system | [docs/04-design-system.md](docs/04-design-system.md) |
| 6 | Arquitetura de software e tecnologia | [docs/05-arquitetura.md](docs/05-arquitetura.md) |
| 7 | Interação, responsividade e estados | [docs/06-interacao-responsividade-estados.md](docs/06-interacao-responsividade-estados.md) |
| 8 | Banco de dados (ER e documentação) | [docs/07-banco-de-dados.md](docs/07-banco-de-dados.md) |
| 9 | SQL DDL inicial (MySQL) | [backend/database/sql/schema_v1.sql](backend/database/sql/schema_v1.sql) |
| 10 | Decisões assumidas e pendências | [docs/09-decisoes-e-pendencias.md](docs/09-decisoes-e-pendencias.md) |

Diagramas em Mermaid (renderizam no GitHub, GitLab, VS Code com extensão e na maioria dos editores Markdown).

Convenção: **S-xx** = suposição adotada; **P-xx** = ponto a confirmar com recomendação.
