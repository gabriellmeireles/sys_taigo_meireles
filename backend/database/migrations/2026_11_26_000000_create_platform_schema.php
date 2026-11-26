<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Cria todas as tabelas da plataforma a partir do DDL versionado em
 * database/sql/schema_v1.sql (fonte única do modelo de dados da V1).
 *
 * O DDL é aplicado como SQL puro porque usa recursos que o Schema Builder
 * não expressa diretamente: CHECK constraints, coluna gerada STORED com
 * índice único (imagem principal) e FULLTEXT com collation específica.
 * Mudanças futuras devem entrar como novas migrações, não neste arquivo.
 */
return new class extends Migration
{
    /** Ordem inversa de criação, para o down(). */
    private const TABLES = [
        'analytics_daily_dimensions', 'analytics_daily_artworks', 'analytics_daily_totals', 'analytics_events',
        'instagram_posts', 'integration_states',
        'contact_messages',
        'auto_reply_templates', 'contact_setting_translations', 'contact_settings',
        'ui_text_translations', 'ui_texts', 'site_setting_translations', 'site_settings',
        'slug_redirects', 'page_section_translations', 'page_sections', 'page_translations', 'pages',
        'press_item_translations', 'press_items',
        'studio_item_translations', 'studio_items',
        'exhibition_artworks', 'exhibition_images', 'exhibition_translations', 'exhibitions',
        'artwork_images', 'artwork_translations', 'artworks',
        'series_translations', 'series', 'technique_translations', 'techniques', 'category_translations', 'categories',
        'youtube_video_translations', 'youtube_videos',
        'media_file_translations', 'media_variants', 'media_files',
        'admin_password_tokens', 'admins',
        'locales',
    ];

    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/schema_v1.sql')));
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
};
