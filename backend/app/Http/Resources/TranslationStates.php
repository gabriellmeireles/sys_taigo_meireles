<?php

namespace App\Http\Resources;

use App\Models\Locale;
use Illuminate\Support\Collection;

/** Estado da tradução por idioma: manual | auto | outdated | missing (selo do painel). */
class TranslationStates
{
    public static function of(Collection $translations): array
    {
        $states = [];
        foreach (Locale::ALL as $locale) {
            $t = $translations->firstWhere('locale_code', $locale);
            $states[$locale] = match (true) {
                $t === null => 'missing',
                (bool) $t->is_outdated => 'outdated',
                default => $t->translation_status,
            };
        }

        return $states;
    }
}
