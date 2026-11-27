<?php

namespace App\Domain\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Slug único por idioma numa tabela *_translations (UNIQUE(locale_code, slug)). */
class Slugger
{
    public function unique(string $table, string $foreignKey, ?int $ownerId, string $locale, string $text, int $max = 200): string
    {
        $base = Str::limit(Str::slug($text) ?: 'item', $max, '');
        $slug = $base;
        $n = 2;

        while (DB::table($table)
            ->where('locale_code', $locale)
            ->where('slug', $slug)
            ->when($ownerId, fn ($q) => $q->where($foreignKey, '!=', $ownerId))
            ->exists()) {
            $suffix = '-'.$n++;
            $slug = Str::limit($base, $max - strlen($suffix), '').$suffix;
        }

        return $slug;
    }
}
