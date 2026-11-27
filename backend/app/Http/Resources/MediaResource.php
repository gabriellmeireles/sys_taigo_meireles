<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MediaFile */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        $preview = $variants->first(fn ($v) => $v->variant === 'small' && $v->format === 'webp')
            ?? $variants->first(fn ($v) => $v->format === 'webp');

        return [
            'id' => $this->id,
            'status' => $this->processing_status,
            'error' => $this->processing_error,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'dominant_color' => $this->dominant_color,
            'preview_url' => $preview?->url(),
            'original_url' => url("/api/v1/admin/media/{$this->id}/original"),
            'variants' => $variants->map(fn ($v) => [
                'variant' => $v->variant,
                'format' => $v->format,
                'width' => $v->width,
                'height' => $v->height,
                'size_bytes' => $v->size_bytes,
                'url' => $v->url(),
            ])->values(),
        ];
    }
}
