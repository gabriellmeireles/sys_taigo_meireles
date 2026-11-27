<?php

namespace App\Http\Resources;

use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Obra completa para o editor do painel. @mixin \App\Models\Artwork */
class ArtworkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translations = [];
        foreach ($this->translations as $t) {
            $translations[$t->locale_code] = [
                'title' => $t->title,
                'slug' => $t->slug,
                'description' => $t->description,
                'dimensions_note' => $t->dimensions_note,
                'seo_title' => $t->seo_title,
                'seo_description' => $t->seo_description,
                'og_title' => $t->og_title,
                'og_description' => $t->og_description,
                'translation_status' => $t->translation_status,
                'is_outdated' => (bool) $t->is_outdated,
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title(Locale::SOURCE),
            'year' => $this->year,
            'height_cm' => $this->height_cm !== null ? (float) $this->height_cm : null,
            'width_cm' => $this->width_cm !== null ? (float) $this->width_cm : null,
            'depth_cm' => $this->depth_cm !== null ? (float) $this->depth_cm : null,
            'category_id' => $this->category_id,
            'technique_id' => $this->technique_id,
            'series_id' => $this->series_id,
            'status' => $this->status->value,
            'show_price' => $this->show_price,
            'price_brl' => $this->price_brl !== null ? (float) $this->price_brl : null,
            'price_usd' => $this->price_usd !== null ? (float) $this->price_usd : null,
            'sort_order' => $this->sort_order,
            'video_url' => $this->video ? "https://www.youtube.com/watch?v={$this->video->youtube_id}" : null,
            'translations' => $translations,
            'translation_states' => TranslationStates::of($this->translations),
            'images' => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'media_file_id' => $img->media_file_id,
                'is_primary' => $img->is_primary,
                'sort_order' => $img->sort_order,
                'media' => new MediaResource($img->media),
            ])->values(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
