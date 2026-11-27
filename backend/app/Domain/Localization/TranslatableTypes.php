<?php

namespace App\Domain\Localization;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Series;
use App\Models\Technique;
use InvalidArgumentException;

/**
 * O que é traduzível e como. "auto" = recebe tradução automática.
 * Nomes de séries, técnicas e categorias são traduzidos à mão (LibreTranslate não
 * tem glossário e erra termos técnicos — decisão da P-28).
 */
final class TranslatableTypes
{
    public const TYPES = [
        'artwork' => [
            'model' => Artwork::class,
            'table' => 'artwork_translations',
            'fk' => 'artwork_id',
            'label' => 'Obra',
            'auto' => true,
            'fields' => ['title', 'description', 'dimensions_note', 'seo_title', 'seo_description', 'og_title', 'og_description'],
            'title' => 'title',
            'slug_max' => 220,
            'audit' => true, // tem translated_at / reviewed_at / reviewed_by_admin_id
        ],
        'series' => [
            'model' => Series::class, 'table' => 'series_translations', 'fk' => 'series_id', 'label' => 'Série',
            'auto' => false, 'fields' => ['name'], 'title' => 'name', 'slug_max' => 180, 'audit' => false,
        ],
        'technique' => [
            'model' => Technique::class, 'table' => 'technique_translations', 'fk' => 'technique_id', 'label' => 'Técnica',
            'auto' => false, 'fields' => ['name'], 'title' => 'name', 'slug_max' => 180, 'audit' => false,
        ],
        'category' => [
            'model' => Category::class, 'table' => 'category_translations', 'fk' => 'category_id', 'label' => 'Categoria',
            'auto' => false, 'fields' => ['name'], 'title' => 'name', 'slug_max' => 180, 'audit' => false,
        ],
    ];

    public static function get(string $type): array
    {
        return self::TYPES[$type] ?? throw new InvalidArgumentException("Tipo traduzível desconhecido: {$type}");
    }

    public static function typeOf(object $model): string
    {
        foreach (self::TYPES as $type => $def) {
            if ($model instanceof $def['model']) {
                return $type;
            }
        }

        throw new InvalidArgumentException('Modelo não traduzível: '.$model::class);
    }
}
