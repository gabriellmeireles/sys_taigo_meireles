<?php

namespace App\Http\Resources;

use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Linha da lista de obras do painel. @mixin \App\Models\Artwork */
class ArtworkListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = $this->primaryImage?->media?->variants ?? collect();
        $thumb = $variants->first(fn ($v) => $v->variant === 'thumb' && $v->format === 'webp');

        return [
            'id' => $this->id,
            'title' => $this->title(Locale::SOURCE),
            'year' => $this->year,
            'status' => $this->status->value,
            'series' => $this->series?->name(Locale::SOURCE),
            'technique' => $this->technique?->name(Locale::SOURCE),
            'show_price' => $this->show_price,
            'price_brl' => $this->price_brl !== null ? (float) $this->price_brl : null,
            'price_usd' => $this->price_usd !== null ? (float) $this->price_usd : null,
            'thumbnail_url' => $thumb?->url(),
            'thumbnail_color' => $this->primaryImage?->media?->dominant_color,
            'translation_states' => TranslationStates::of($this->translations),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
