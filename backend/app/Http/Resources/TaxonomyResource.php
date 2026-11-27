<?php

namespace App\Http\Resources;

use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Categoria, técnica ou série. @mixin \App\Models\Taxonomy */
class TaxonomyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translations = [];
        foreach ($this->translations as $t) {
            $translations[$t->locale_code] = [
                'name' => $t->name,
                'slug' => $t->slug,
                'translation_status' => $t->translation_status,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name(Locale::SOURCE),
            'sort_order' => $this->sort_order,
            'artworks_count' => $this->artworks_count ?? null,
            'translations' => $translations,
            'translation_states' => TranslationStates::of($this->translations),
        ];
    }
}
