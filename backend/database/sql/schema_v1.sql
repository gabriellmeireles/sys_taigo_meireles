-- =====================================================================
-- Plataforma Taigo Meireles — DDL inicial (proposta)
-- =====================================================================
-- SUPOSIÇÕES (ver docs/07-banco-de-dados.md §8.8 e docs/09-decisoes-e-pendencias.md):
--  S-DB1  MySQL 8.0.16+ (CHECK constraints aplicadas, colunas geradas, JSON).
--         Se a Hostinger oferecer MariaDB, revisar CHECKs e colunas geradas.
--  S-DB2  Engine InnoDB, charset utf8mb4, collation utf8mb4_0900_ai_ci
--         (busca sem acento e sem caixa: "mare" encontra "Maré").
--  S-DB3  Datas/horas gravadas em UTC (DATETIME); fuso de exibição em site_settings.
--  S-DB4  Idiomas fixos: pt-BR (origem padrão), en (fallback do site), es.
--  S-DB5  Todo texto traduzível vive em tabelas *_translations, inclusive o
--         idioma de origem. Ausência de linha = tradução ausente.
--  S-DB6  Arquivos ficam no disco; o banco guarda caminhos relativos e metadados.
--  S-DB7  Tabelas de infraestrutura do Laravel (sessions, jobs, failed_jobs,
--         cache, migrations) são criadas pelas migrações do framework e não
--         estão aqui. Em produção, este DDL deve virar migrações do Laravel.
--  S-DB8  Sem tabelas de newsletter, pedidos, pagamentos, clientes ou colecionadores.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- 1. IDIOMAS
-- ---------------------------------------------------------------------
CREATE TABLE locales (
  code            VARCHAR(5)   NOT NULL,            -- 'pt-BR', 'en', 'es'
  url_prefix      VARCHAR(5)   NOT NULL,            -- 'pt', 'en', 'es'
  name            VARCHAR(40)  NOT NULL,            -- 'Português'
  currency_code   CHAR(3)      NOT NULL,            -- 'BRL' | 'USD'
  is_source       TINYINT(1)   NOT NULL DEFAULT 0,  -- idioma de origem do conteúdo
  is_fallback     TINYINT(1)   NOT NULL DEFAULT 0,  -- idioma para navegador não suportado
  sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (code),
  UNIQUE KEY uq_locales_prefix (url_prefix),
  CONSTRAINT ck_locales_currency CHECK (currency_code IN ('BRL','USD'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO locales (code, url_prefix, name, currency_code, is_source, is_fallback, sort_order) VALUES
 ('pt-BR','pt','Português','BRL',1,0,1),
 ('en','en','English','USD',0,1,2),
 ('es','es','Español','USD',0,0,3);

-- ---------------------------------------------------------------------
-- 2. ADMINISTRADORES
-- ---------------------------------------------------------------------
CREATE TABLE admins (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name              VARCHAR(120) NOT NULL,
  email             VARCHAR(191) NOT NULL,
  password_hash     VARCHAR(255) NULL,               -- NULL até aceitar o convite
  is_active         TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at     DATETIME     NULL,
  password_changed_at DATETIME   NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tokens de redefinição de senha e de convite (guardados com hash, uso único)
CREATE TABLE admin_password_tokens (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id      BIGINT UNSIGNED NOT NULL,
  purpose       ENUM('reset','invite') NOT NULL,
  token_hash    CHAR(64)     NOT NULL,               -- SHA-256 do token enviado por e-mail
  expires_at    DATETIME     NOT NULL,
  used_at       DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_apt_token (token_hash),
  KEY ix_apt_admin (admin_id, purpose),
  CONSTRAINT fk_apt_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 3. MÍDIA (arquivo original + variantes otimizadas) e DOCUMENTOS
-- ---------------------------------------------------------------------
CREATE TABLE media_files (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid               CHAR(36)     NOT NULL,
  kind               ENUM('image','document') NOT NULL,
  disk               VARCHAR(32)  NOT NULL DEFAULT 'local',  -- disco de armazenamento (config Laravel)
  original_path      VARCHAR(255) NOT NULL,                  -- relativo: media/originals/ab/{uuid}.tif
  original_filename  VARCHAR(255) NOT NULL,                  -- nome enviado pelo admin
  mime_type          VARCHAR(100) NOT NULL,
  size_bytes         BIGINT UNSIGNED NOT NULL,
  width              INT UNSIGNED NULL,                      -- imagens
  height             INT UNSIGNED NULL,
  color_profile      VARCHAR(100) NULL,                      -- ex.: 'Adobe RGB (1998)'
  dominant_color     CHAR(7)      NULL,                      -- '#A39E98' (placeholder)
  focal_x            DECIMAL(4,3) NULL,                      -- 0..1 para recortes de fotos (não obras)
  focal_y            DECIMAL(4,3) NULL,
  checksum_sha256    CHAR(64)     NOT NULL,
  processing_status  ENUM('pending','processing','ready','failed') NOT NULL DEFAULT 'pending',
  processing_error   VARCHAR(500) NULL,
  uploaded_by_admin_id BIGINT UNSIGNED NULL,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_media_uuid (uuid),
  UNIQUE KEY uq_media_original_path (original_path),
  KEY ix_media_checksum (checksum_sha256),                   -- detectar duplicatas (não único: reuso permitido)
  KEY ix_media_kind_status (kind, processing_status),
  CONSTRAINT fk_media_admin FOREIGN KEY (uploaded_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL,
  CONSTRAINT ck_media_focal CHECK ((focal_x IS NULL OR focal_x BETWEEN 0 AND 1) AND (focal_y IS NULL OR focal_y BETWEEN 0 AND 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Versões derivadas geradas a partir do original (nunca o original)
CREATE TABLE media_variants (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  media_file_id  BIGINT UNSIGNED NOT NULL,
  variant        ENUM('thumb','small','medium','large','zoom','og') NOT NULL,  -- 480/960/1600/2400/1200x630
  format         ENUM('webp','jpeg','avif') NOT NULL,
  path           VARCHAR(255) NOT NULL,                       -- relativo a MEDIA_BASE_URL
  width          INT UNSIGNED NOT NULL,
  height         INT UNSIGNED NOT NULL,
  size_bytes     BIGINT UNSIGNED NOT NULL,
  quality        TINYINT UNSIGNED NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_variant (media_file_id, variant, format),
  UNIQUE KEY uq_variant_path (path),
  CONSTRAINT fk_variant_media FOREIGN KEY (media_file_id) REFERENCES media_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Texto alternativo / legenda / idioma do documento, por idioma
CREATE TABLE media_file_translations (
  media_file_id      BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  alt_text           VARCHAR(300) NULL,
  caption            VARCHAR(500) NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (media_file_id, locale_code),
  CONSTRAINT fk_mft_media  FOREIGN KEY (media_file_id) REFERENCES media_files(id) ON DELETE CASCADE,
  CONSTRAINT fk_mft_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 4. VÍDEOS DO YOUTUBE
-- ---------------------------------------------------------------------
CREATE TABLE youtube_videos (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  youtube_id         VARCHAR(20)  NOT NULL,                  -- ID extraído da URL (11 caracteres hoje)
  original_url       VARCHAR(500) NOT NULL,
  start_seconds      INT UNSIGNED NULL,
  thumbnail_media_id BIGINT UNSIGNED NULL,                   -- capa personalizada opcional
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_youtube_id (youtube_id),
  CONSTRAINT fk_yt_thumb FOREIGN KEY (thumbnail_media_id) REFERENCES media_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE youtube_video_translations (
  youtube_video_id   BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  title              VARCHAR(200) NOT NULL,
  description        TEXT         NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (youtube_video_id, locale_code),
  CONSTRAINT fk_ytt_video  FOREIGN KEY (youtube_video_id) REFERENCES youtube_videos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ytt_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 5. TAXONOMIAS: CATEGORIAS, TÉCNICAS, SÉRIES
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE category_translations (
  category_id        BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  name               VARCHAR(120) NOT NULL,
  slug               VARCHAR(140) NOT NULL,                  -- usado nos filtros da URL
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id, locale_code),
  UNIQUE KEY uq_cat_slug (locale_code, slug),
  CONSTRAINT fk_catt_cat    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  CONSTRAINT fk_catt_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE techniques (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE technique_translations (
  technique_id       BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  name               VARCHAR(160) NOT NULL,                  -- 'Óleo sobre tela'
  slug               VARCHAR(180) NOT NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (technique_id, locale_code),
  UNIQUE KEY uq_tech_slug (locale_code, slug),
  CONSTRAINT fk_techt_tech   FOREIGN KEY (technique_id) REFERENCES techniques(id) ON DELETE CASCADE,
  CONSTRAINT fk_techt_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE series (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cover_media_id  BIGINT UNSIGNED NULL,
  start_year      SMALLINT UNSIGNED NULL,
  end_year        SMALLINT UNSIGNED NULL,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_series_cover FOREIGN KEY (cover_media_id) REFERENCES media_files(id) ON DELETE SET NULL,
  CONSTRAINT ck_series_years CHECK (end_year IS NULL OR start_year IS NULL OR end_year >= start_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE series_translations (
  series_id          BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  name               VARCHAR(160) NOT NULL,
  slug               VARCHAR(180) NOT NULL,
  description        TEXT         NULL,
  seo_title          VARCHAR(70)  NULL,
  seo_description    VARCHAR(170) NULL,
  og_title           VARCHAR(100) NULL,
  og_description     VARCHAR(200) NULL,
  og_image_media_id  BIGINT UNSIGNED NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (series_id, locale_code),
  UNIQUE KEY uq_series_slug (locale_code, slug),
  CONSTRAINT fk_sert_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
  CONSTRAINT fk_sert_locale FOREIGN KEY (locale_code) REFERENCES locales(code),
  CONSTRAINT fk_sert_og     FOREIGN KEY (og_image_media_id) REFERENCES media_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 6. OBRAS
-- ---------------------------------------------------------------------
CREATE TABLE artworks (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  year              SMALLINT UNSIGNED NOT NULL,
  height_cm         DECIMAL(7,2) NULL,
  width_cm          DECIMAL(7,2) NULL,
  depth_cm          DECIMAL(7,2) NULL,
  category_id       BIGINT UNSIGNED NOT NULL,
  technique_id      BIGINT UNSIGNED NOT NULL,
  series_id         BIGINT UNSIGNED NOT NULL,                 -- P-06: série obrigatória (decidido em 26/11/2026)
  youtube_video_id  BIGINT UNSIGNED NULL,                     -- vídeo opcional
  status            ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',
  price_brl         DECIMAL(12,2) NULL,                       -- preenchido manualmente
  price_usd         DECIMAL(12,2) NULL,                       -- preenchido manualmente
  show_price        TINYINT(1)   NOT NULL DEFAULT 0,          -- opção individual
  sort_order        INT          NOT NULL DEFAULT 0,          -- ordem manual na galeria
  status_changed_at DATETIME     NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_artworks_status_order (status, sort_order, year),
  KEY ix_artworks_year (year),
  KEY ix_artworks_category (category_id),
  KEY ix_artworks_technique (technique_id),
  KEY ix_artworks_series (series_id),
  CONSTRAINT fk_art_category  FOREIGN KEY (category_id)  REFERENCES categories(id) ON DELETE RESTRICT,
  CONSTRAINT fk_art_technique FOREIGN KEY (technique_id) REFERENCES techniques(id) ON DELETE RESTRICT,
  CONSTRAINT fk_art_series    FOREIGN KEY (series_id)    REFERENCES series(id)     ON DELETE RESTRICT,
  CONSTRAINT fk_art_video     FOREIGN KEY (youtube_video_id) REFERENCES youtube_videos(id) ON DELETE SET NULL,
  CONSTRAINT ck_art_year   CHECK (year BETWEEN 1900 AND 2100),
  CONSTRAINT ck_art_prices CHECK ((price_brl IS NULL OR price_brl >= 0) AND (price_usd IS NULL OR price_usd >= 0)),
  CONSTRAINT ck_art_dims   CHECK ((height_cm IS NULL OR height_cm > 0) AND (width_cm IS NULL OR width_cm > 0) AND (depth_cm IS NULL OR depth_cm > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE artwork_translations (
  artwork_id         BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  title              VARCHAR(200) NOT NULL,
  slug               VARCHAR(220) NOT NULL,
  description        TEXT         NULL,                       -- HTML sanitizado (subconjunto)
  dimensions_note    VARCHAR(200) NULL,                       -- "díptico, 2 × 60 × 80 cm"
  seo_title          VARCHAR(70)  NULL,
  seo_description    VARCHAR(170) NULL,
  og_title           VARCHAR(100) NULL,
  og_description     VARCHAR(200) NULL,
  og_image_media_id  BIGINT UNSIGNED NULL,                    -- padrão: imagem principal
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,         -- origem mudou depois da revisão manual
  source_hash        CHAR(64)     NULL,                       -- hash do texto de origem usado
  translated_at      DATETIME     NULL,
  reviewed_at        DATETIME     NULL,
  reviewed_by_admin_id BIGINT UNSIGNED NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (artwork_id, locale_code),
  UNIQUE KEY uq_artwork_slug (locale_code, slug),
  KEY ix_artt_status (locale_code, translation_status, is_outdated),
  FULLTEXT KEY ft_artt_title (title),                          -- busca por nome (alternativa: LIKE)
  CONSTRAINT fk_artt_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
  CONSTRAINT fk_artt_locale  FOREIGN KEY (locale_code) REFERENCES locales(code),
  CONSTRAINT fk_artt_og      FOREIGN KEY (og_image_media_id) REFERENCES media_files(id) ON DELETE SET NULL,
  CONSTRAINT fk_artt_admin   FOREIGN KEY (reviewed_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE artwork_images (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  artwork_id     BIGINT UNSIGNED NOT NULL,
  media_file_id  BIGINT UNSIGNED NOT NULL,
  sort_order     INT NOT NULL DEFAULT 0,
  is_primary     TINYINT(1) NOT NULL DEFAULT 0,
  -- Garante no máximo UMA imagem principal por obra (MySQL não tem índice parcial):
  primary_guard  BIGINT UNSIGNED AS (IF(is_primary = 1, artwork_id, NULL)) STORED,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_artimg_media (artwork_id, media_file_id),
  UNIQUE KEY uq_artimg_primary (primary_guard),
  KEY ix_artimg_order (artwork_id, sort_order),
  -- RESTRICT (e não CASCADE): o MySQL proíbe ação CASCADE/SET NULL em FK cuja coluna-base
  -- alimenta uma coluna gerada STORED. Ao excluir uma obra, a API apaga antes as linhas
  -- de artwork_images na mesma transação.
  CONSTRAINT fk_artimg_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE RESTRICT,
  CONSTRAINT fk_artimg_media   FOREIGN KEY (media_file_id) REFERENCES media_files(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Regra de aplicação: "ao menos 1 imagem" e "exatamente 1 principal quando houver imagens"
-- são garantidas na API (transação ao reordenar / trocar principal: desmarca a atual e
-- marca a nova na mesma transação).

-- ---------------------------------------------------------------------
-- 7. EXPOSIÇÕES
-- ---------------------------------------------------------------------
CREATE TABLE exhibitions (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  start_date       DATE NOT NULL,
  end_date         DATE NULL,                                 -- NULL = evento de um dia
  venue_name       VARCHAR(200) NOT NULL,                     -- nome próprio, não traduzido
  city             VARCHAR(120) NULL,
  country_code     CHAR(2)      NULL,                         -- ISO 3166-1
  venue_url        VARCHAR(500) NULL,
  cover_media_id   BIGINT UNSIGNED NULL,
  youtube_video_id BIGINT UNSIGNED NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_exh_dates (start_date, end_date),
  CONSTRAINT fk_exh_cover FOREIGN KEY (cover_media_id) REFERENCES media_files(id) ON DELETE SET NULL,
  CONSTRAINT fk_exh_video FOREIGN KEY (youtube_video_id) REFERENCES youtube_videos(id) ON DELETE SET NULL,
  CONSTRAINT ck_exh_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Futura / atual / passada é CALCULADA a partir das datas (não armazenada).

CREATE TABLE exhibition_translations (
  exhibition_id      BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  title              VARCHAR(200) NOT NULL,
  slug               VARCHAR(220) NOT NULL,
  summary            VARCHAR(400) NULL,
  description        TEXT         NULL,
  location_note      VARCHAR(200) NULL,                       -- "Sala 2, térreo"
  seo_title          VARCHAR(70)  NULL,
  seo_description    VARCHAR(170) NULL,
  og_title           VARCHAR(100) NULL,
  og_description     VARCHAR(200) NULL,
  og_image_media_id  BIGINT UNSIGNED NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  translated_at      DATETIME     NULL,
  reviewed_at        DATETIME     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (exhibition_id, locale_code),
  UNIQUE KEY uq_exh_slug (locale_code, slug),
  CONSTRAINT fk_exht_exh    FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
  CONSTRAINT fk_exht_locale FOREIGN KEY (locale_code) REFERENCES locales(code),
  CONSTRAINT fk_exht_og     FOREIGN KEY (og_image_media_id) REFERENCES media_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE exhibition_images (
  exhibition_id  BIGINT UNSIGNED NOT NULL,
  media_file_id  BIGINT UNSIGNED NOT NULL,
  sort_order     INT NOT NULL DEFAULT 0,
  PRIMARY KEY (exhibition_id, media_file_id),
  KEY ix_exhimg_order (exhibition_id, sort_order),
  CONSTRAINT fk_exhimg_exh   FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
  CONSTRAINT fk_exhimg_media FOREIGN KEY (media_file_id) REFERENCES media_files(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Opcional (sugestão): obras exibidas na exposição
CREATE TABLE exhibition_artworks (
  exhibition_id  BIGINT UNSIGNED NOT NULL,
  artwork_id     BIGINT UNSIGNED NOT NULL,
  sort_order     INT NOT NULL DEFAULT 0,
  PRIMARY KEY (exhibition_id, artwork_id),
  KEY ix_exhart_artwork (artwork_id),
  CONSTRAINT fk_exhart_exh FOREIGN KEY (exhibition_id) REFERENCES exhibitions(id) ON DELETE CASCADE,
  CONSTRAINT fk_exhart_art FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 8. ATELIÊ
-- ---------------------------------------------------------------------
CREATE TABLE studio_items (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_type        ENUM('photo','video') NOT NULL,
  theme            ENUM('space','process','backstage') NOT NULL,
  media_file_id    BIGINT UNSIGNED NULL,
  youtube_video_id BIGINT UNSIGNED NULL,
  layout_hint      ENUM('normal','wide','tall') NOT NULL DEFAULT 'normal',  -- composição do mosaico
  sort_order       INT NOT NULL DEFAULT 0,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_studio_order (theme, sort_order),
  CONSTRAINT fk_studio_media FOREIGN KEY (media_file_id) REFERENCES media_files(id) ON DELETE RESTRICT,
  CONSTRAINT fk_studio_video FOREIGN KEY (youtube_video_id) REFERENCES youtube_videos(id) ON DELETE RESTRICT,
  CONSTRAINT ck_studio_type CHECK (
    (item_type = 'photo' AND media_file_id IS NOT NULL AND youtube_video_id IS NULL) OR
    (item_type = 'video' AND youtube_video_id IS NOT NULL AND media_file_id IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE studio_item_translations (
  studio_item_id     BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  title              VARCHAR(200) NULL,
  caption            VARCHAR(500) NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (studio_item_id, locale_code),
  CONSTRAINT fk_stt_item   FOREIGN KEY (studio_item_id) REFERENCES studio_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_stt_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 9. IMPRENSA
-- ---------------------------------------------------------------------
CREATE TABLE press_items (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_type          ENUM('article','interview','video','document','link') NOT NULL,
  outlet_name        VARCHAR(160) NULL,                       -- veículo
  published_on       DATE         NULL,
  external_url       VARCHAR(500) NULL,
  youtube_video_id   BIGINT UNSIGNED NULL,
  document_media_id  BIGINT UNSIGNED NULL,                    -- PDF (media_files.kind = 'document')
  document_locale    VARCHAR(5)   NULL,                       -- idioma do PDF
  cover_media_id     BIGINT UNSIGNED NULL,
  is_press_kit       TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order         INT NOT NULL DEFAULT 0,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_press_type_date (item_type, published_on),
  CONSTRAINT fk_press_video FOREIGN KEY (youtube_video_id) REFERENCES youtube_videos(id) ON DELETE RESTRICT,  -- RESTRICT: coluna usada no CHECK ck_press_content
  CONSTRAINT fk_press_doc   FOREIGN KEY (document_media_id) REFERENCES media_files(id) ON DELETE RESTRICT,
  CONSTRAINT fk_press_cover FOREIGN KEY (cover_media_id) REFERENCES media_files(id) ON DELETE SET NULL,
  CONSTRAINT fk_press_doclocale FOREIGN KEY (document_locale) REFERENCES locales(code),
  CONSTRAINT ck_press_content CHECK (external_url IS NOT NULL OR youtube_video_id IS NOT NULL OR document_media_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE press_item_translations (
  press_item_id      BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  title              VARCHAR(250) NOT NULL,
  summary            TEXT         NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (press_item_id, locale_code),
  CONSTRAINT fk_prt_item   FOREIGN KEY (press_item_id) REFERENCES press_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_prt_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 10. PÁGINAS E SEÇÕES
-- ---------------------------------------------------------------------
CREATE TABLE pages (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_key    ENUM('home','about','gallery','available','exhibitions','studio','press','contact','privacy','terms') NOT NULL,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pages_key (page_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE page_translations (
  page_id            BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  title              VARCHAR(200) NOT NULL,
  slug               VARCHAR(120) NOT NULL,                   -- segmento da URL ('galeria', 'gallery')
  intro              TEXT         NULL,
  body               MEDIUMTEXT   NULL,                       -- textos longos (Privacidade, Termos)
  seo_title          VARCHAR(70)  NULL,
  seo_description    VARCHAR(170) NULL,
  og_title           VARCHAR(100) NULL,
  og_description     VARCHAR(200) NULL,
  og_image_media_id  BIGINT UNSIGNED NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  translated_at      DATETIME     NULL,
  reviewed_at        DATETIME     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (page_id, locale_code),
  UNIQUE KEY uq_page_slug (locale_code, slug),
  CONSTRAINT fk_pgt_page   FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
  CONSTRAINT fk_pgt_locale FOREIGN KEY (locale_code) REFERENCES locales(code),
  CONSTRAINT fk_pgt_og     FOREIGN KEY (og_image_media_id) REFERENCES media_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Blocos ordenáveis das páginas Início e Sobre
CREATE TABLE page_sections (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_id           BIGINT UNSIGNED NOT NULL,
  section_type      ENUM(
                      'hero_artwork','manifesto','intro','recent_artworks','exhibitions',
                      'studio_teaser','instagram','contact_cta',            -- Início
                      'text','image_text','quote','wide_image','image_pair','timeline'  -- Sobre / genéricos
                    ) NOT NULL,
  sort_order        INT NOT NULL DEFAULT 0,
  is_visible        TINYINT(1) NOT NULL DEFAULT 1,             -- ocultar seção (não é rascunho)
  artwork_id        BIGINT UNSIGNED NULL,                      -- hero_artwork
  media_file_id     BIGINT UNSIGNED NULL,                      -- imagem principal do bloco
  media_file_id_2   BIGINT UNSIGNED NULL,                      -- image_pair
  settings          JSON NULL,                                 -- {"image_side":"left","tone":"dark","limit":4}
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_ps_order (page_id, sort_order),
  CONSTRAINT fk_ps_page    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
  CONSTRAINT fk_ps_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE SET NULL,
  CONSTRAINT fk_ps_media   FOREIGN KEY (media_file_id) REFERENCES media_files(id) ON DELETE SET NULL,
  CONSTRAINT fk_ps_media2  FOREIGN KEY (media_file_id_2) REFERENCES media_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE page_section_translations (
  page_section_id    BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  heading            VARCHAR(250) NULL,
  body               TEXT         NULL,
  attribution        VARCHAR(200) NULL,                       -- citação: "— Taigo Meireles"
  cta_label          VARCHAR(80)  NULL,
  items              JSON NULL,                               -- linha do tempo: [{"year":2016,"text":"..."}]
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (page_section_id, locale_code),
  CONSTRAINT fk_pst_section FOREIGN KEY (page_section_id) REFERENCES page_sections(id) ON DELETE CASCADE,
  CONSTRAINT fk_pst_locale  FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Redirecionamentos 301 quando um slug muda (SEO)
CREATE TABLE slug_redirects (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  locale_code  VARCHAR(5)   NOT NULL,
  old_path     VARCHAR(300) NOT NULL,                         -- '/pt/obras/nome-antigo'
  new_path     VARCHAR(300) NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_redirect_old (old_path),
  CONSTRAINT fk_redir_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 11. CONFIGURAÇÕES (linhas únicas) e TEXTOS DA INTERFACE
-- ---------------------------------------------------------------------
CREATE TABLE site_settings (
  id                     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  timezone               VARCHAR(64)  NOT NULL DEFAULT 'America/Sao_Paulo',
  default_og_media_id    BIGINT UNSIGNED NULL,
  hide_price_unless_available TINYINT(1) NOT NULL DEFAULT 1,  -- P-05 (regra recomendada)
  artwork_frame_bg       ENUM('white','mist') NOT NULL DEFAULT 'white',
  message_retention_days SMALLINT UNSIGNED NULL,               -- NULL = manter até exclusão manual
  analytics_raw_retention_days SMALLINT UNSIGNED NOT NULL DEFAULT 90,
  instagram_feed_enabled TINYINT(1) NOT NULL DEFAULT 1,
  instagram_feed_limit   TINYINT UNSIGNED NOT NULL DEFAULT 6,
  social_links           JSON NULL,                            -- outros perfis (para JSON-LD sameAs)
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_site_og FOREIGN KEY (default_og_media_id) REFERENCES media_files(id) ON DELETE SET NULL,
  CONSTRAINT ck_site_single CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE site_setting_translations (
  site_setting_id    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  locale_code        VARCHAR(5)   NOT NULL,
  site_name          VARCHAR(120) NOT NULL,
  seo_title_suffix   VARCHAR(60)  NULL,                       -- ' — Taigo Meireles'
  default_seo_description VARCHAR(170) NULL,
  footer_tagline     VARCHAR(200) NULL,
  sold_notice        VARCHAR(250) NULL,                       -- "Obra vendida — coleção particular."
  reserved_notice    VARCHAR(250) NULL,
  price_on_request_label VARCHAR(60) NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (site_setting_id, locale_code),
  CONSTRAINT fk_sst_site   FOREIGN KEY (site_setting_id) REFERENCES site_settings(id) ON DELETE CASCADE,
  CONSTRAINT fk_sst_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Textos fixos da interface do site (rótulos, mensagens) editáveis — sugestão S-09
CREATE TABLE ui_texts (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  text_key    VARCHAR(120) NOT NULL,                          -- 'contact.form.success.title'
  context     VARCHAR(255) NULL,                              -- ajuda para quem traduz
  PRIMARY KEY (id),
  UNIQUE KEY uq_ui_key (text_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE ui_text_translations (
  ui_text_id         BIGINT UNSIGNED NOT NULL,
  locale_code        VARCHAR(5)   NOT NULL,
  value              TEXT NOT NULL,
  translation_status ENUM('auto','manual') NOT NULL DEFAULT 'manual',
  is_outdated        TINYINT(1)   NOT NULL DEFAULT 0,
  source_hash        CHAR(64)     NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ui_text_id, locale_code),
  CONSTRAINT fk_uit_text   FOREIGN KEY (ui_text_id) REFERENCES ui_texts(id) ON DELETE CASCADE,
  CONSTRAINT fk_uit_locale FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Contato: links, dados públicos e e-mail destinatário (linha única)
CREATE TABLE contact_settings (
  id                    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  recipient_email       VARCHAR(191) NOT NULL,                -- ÚNICO destinatário do formulário
  whatsapp_number       VARCHAR(20)  NULL,                    -- E.164 sem '+': 5511999999999
  instagram_username    VARCHAR(60)  NULL,                    -- sem '@'
  instagram_url         VARCHAR(255) NULL,
  public_email          VARCHAR(191) NULL,
  public_phone          VARCHAR(30)  NULL,
  studio_city           VARCHAR(120) NULL,
  updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT ck_contact_single CHECK (id = 1),
  CONSTRAINT ck_contact_wa CHECK (whatsapp_number IS NULL OR whatsapp_number REGEXP '^[1-9][0-9]{7,14}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE contact_setting_translations (
  contact_setting_id        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  locale_code               VARCHAR(5) NOT NULL,
  whatsapp_general_message  VARCHAR(500) NULL,                -- "Olá! Vim pelo site e gostaria de…"
  whatsapp_artwork_message  VARCHAR(500) NULL,                -- "Olá! Tenho interesse em {titulo} ({ano}) {url}"
  instagram_copy_text       VARCHAR(500) NULL,                -- referência copiada (P-10)
  updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (contact_setting_id, locale_code),
  CONSTRAINT fk_cst_contact FOREIGN KEY (contact_setting_id) REFERENCES contact_settings(id) ON DELETE CASCADE,
  CONSTRAINT fk_cst_locale  FOREIGN KEY (locale_code) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Confirmação automática ao visitante, por idioma (texto manual, sem tradução automática)
CREATE TABLE auto_reply_templates (
  locale_code   VARCHAR(5)   NOT NULL,
  is_enabled    TINYINT(1)   NOT NULL DEFAULT 1,
  subject       VARCHAR(200) NOT NULL,
  body          TEXT         NOT NULL,                        -- variáveis: {nome} {mensagem} {site}
  updated_by_admin_id BIGINT UNSIGNED NULL,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (locale_code),
  CONSTRAINT fk_arp_locale FOREIGN KEY (locale_code) REFERENCES locales(code),
  CONSTRAINT fk_arp_admin  FOREIGN KEY (updated_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 12. MENSAGENS RECEBIDAS
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                 VARCHAR(120)  NOT NULL,
  email                VARCHAR(191)  NOT NULL,
  phone                VARCHAR(30)   NULL,
  message              TEXT          NOT NULL,
  locale_code          VARCHAR(5)    NOT NULL,                -- idioma do site no envio (define a confirmação)
  artwork_id           BIGINT UNSIGNED NULL,                  -- consulta iniciada em uma obra
  source_path          VARCHAR(300)  NULL,                    -- página de origem
  is_read              TINYINT(1)    NOT NULL DEFAULT 0,
  read_at              DATETIME      NULL,
  notification_status  ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  auto_reply_status    ENUM('pending','sent','failed','disabled') NOT NULL DEFAULT 'pending',
  last_mail_error      VARCHAR(500)  NULL,
  created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_msg_inbox (is_read, created_at),
  KEY ix_msg_created (created_at),
  KEY ix_msg_artwork (artwork_id),
  CONSTRAINT fk_msg_locale  FOREIGN KEY (locale_code) REFERENCES locales(code),
  CONSTRAINT fk_msg_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Sem IP, sem User-Agent. Rate limit anti-spam usa cache temporário, não o banco.
-- Exclusão pelo painel = DELETE definitivo (minimização de dados).

-- ---------------------------------------------------------------------
-- 13. INTEGRAÇÕES (status, sem segredos) e CACHE DO INSTAGRAM
-- ---------------------------------------------------------------------
CREATE TABLE integration_states (
  integration_key  ENUM('mail','translation','instagram','geoip','antispam') NOT NULL,
  status           ENUM('not_configured','active','error') NOT NULL DEFAULT 'not_configured',
  provider_label   VARCHAR(80)  NULL,                         -- informativo; segredos ficam no .env
  last_success_at  DATETIME     NULL,
  last_error_at    DATETIME     NULL,
  last_error       VARCHAR(500) NULL,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (integration_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE instagram_posts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  external_id     VARCHAR(64)  NULL,                          -- NULL para post curado manualmente
  permalink       VARCHAR(500) NOT NULL,
  media_type      ENUM('image','video','carousel') NOT NULL DEFAULT 'image',
  thumbnail_media_id BIGINT UNSIGNED NULL,                    -- cópia local opcional (ver P-22)
  remote_image_url VARCHAR(1000) NULL,
  caption         TEXT NULL,
  posted_at       DATETIME NULL,
  is_hidden       TINYINT(1) NOT NULL DEFAULT 0,              -- admin pode ocultar um post
  source          ENUM('api','manual') NOT NULL DEFAULT 'api',
  fetched_at      DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ig_external (external_id),
  KEY ix_ig_posted (is_hidden, posted_at),
  CONSTRAINT fk_ig_thumb FOREIGN KEY (thumbnail_media_id) REFERENCES media_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 14. MÉTRICAS (pseudonimizadas e agregadas)
-- ---------------------------------------------------------------------
-- 14.1 Eventos brutos — retenção curta (site_settings.analytics_raw_retention_days)
CREATE TABLE analytics_events (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  occurred_at       DATETIME     NOT NULL,                    -- UTC
  event_type        ENUM('page_view','artwork_view','whatsapp_click','instagram_click','email_cta_click','contact_submit') NOT NULL,
  page_key          VARCHAR(40)  NULL,                        -- 'gallery', 'artwork', ...
  path              VARCHAR(300) NULL,                        -- sem query string
  artwork_id        BIGINT UNSIGNED NULL,
  site_locale       VARCHAR(5)   NOT NULL,
  browser_language  CHAR(2)      NULL,                        -- 'pt','en','fr'... (agregável)
  country_code      CHAR(2)      NULL,                        -- derivado do IP em memória; IP NÃO armazenado
  channel           ENUM('direct','search','social','referral','campaign','internal','unknown') NOT NULL DEFAULT 'unknown',
  referrer_domain   VARCHAR(120) NULL,                        -- só o domínio
  utm_source        VARCHAR(80)  NULL,
  utm_medium        VARCHAR(80)  NULL,
  utm_campaign      VARCHAR(120) NULL,
  device_type       ENUM('mobile','tablet','desktop','unknown') NOT NULL DEFAULT 'unknown',
  visitor_day_hash  BINARY(32)   NOT NULL,                    -- SHA-256(sal diário + IP + UA + host); PSEUDONIMIZADO, sal descartado
  PRIMARY KEY (id),
  KEY ix_ae_time_type (occurred_at, event_type),
  KEY ix_ae_artwork_time (artwork_id, occurred_at),
  KEY ix_ae_visitor (visitor_day_hash),
  CONSTRAINT fk_ae_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE SET NULL,
  CONSTRAINT fk_ae_locale  FOREIGN KEY (site_locale) REFERENCES locales(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 14.2 Totais diários — AGREGADO (mantido indefinidamente)
CREATE TABLE analytics_daily_totals (
  day               DATE NOT NULL,                            -- no fuso do site
  page_views        INT UNSIGNED NOT NULL DEFAULT 0,          -- acessos totais
  unique_visitors   INT UNSIGNED NOT NULL DEFAULT 0,          -- visitantes únicos do dia
  artwork_views     INT UNSIGNED NOT NULL DEFAULT 0,
  whatsapp_clicks   INT UNSIGNED NOT NULL DEFAULT 0,
  instagram_clicks  INT UNSIGNED NOT NULL DEFAULT 0,
  email_cta_clicks  INT UNSIGNED NOT NULL DEFAULT 0,
  contact_messages  INT UNSIGNED NOT NULL DEFAULT 0,
  aggregated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 14.3 Por obra e dia — AGREGADO
CREATE TABLE analytics_daily_artworks (
  day               DATE NOT NULL,
  artwork_id        BIGINT UNSIGNED NOT NULL,
  views             INT UNSIGNED NOT NULL DEFAULT 0,
  unique_visitors   INT UNSIGNED NOT NULL DEFAULT 0,
  whatsapp_clicks   INT UNSIGNED NOT NULL DEFAULT 0,
  instagram_clicks  INT UNSIGNED NOT NULL DEFAULT 0,
  email_cta_clicks  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (day, artwork_id),
  KEY ix_ada_artwork (artwork_id, day),
  CONSTRAINT fk_ada_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 14.4 Por dimensão e dia (origem, domínio, país, idioma, dispositivo) — AGREGADO
CREATE TABLE analytics_daily_dimensions (
  day               DATE NOT NULL,
  dimension         ENUM('channel','referrer_domain','utm_source','country','site_locale','browser_language','device') NOT NULL,
  dimension_value   VARCHAR(120) NOT NULL,                    -- '' para desconhecido
  page_views        INT UNSIGNED NOT NULL DEFAULT 0,
  unique_visitors   INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (day, dimension, dimension_value),
  KEY ix_add_dim (dimension, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 15. DADOS INICIAIS MÍNIMOS
-- ---------------------------------------------------------------------
INSERT INTO pages (page_key) VALUES
 ('home'),('about'),('gallery'),('available'),('exhibitions'),('studio'),('press'),('contact'),('privacy'),('terms');

INSERT INTO site_settings (id) VALUES (1);

INSERT INTO integration_states (integration_key) VALUES
 ('mail'),('translation'),('instagram'),('geoip'),('antispam');

-- contact_settings (id=1) e auto_reply_templates (3 idiomas) devem ser criados no
-- primeiro acesso ao painel ou por seeder, pois exigem o e-mail destinatário real.
