<?php

namespace App\Domain\Catalog;

use App\Models\Artwork;
use App\Models\ArtworkImage;
use App\Models\ArtworkStatus;
use App\Models\Locale;
use App\Models\YoutubeVideo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Cria e atualiza obras numa única transação: dados, traduções (slug único por idioma),
 * vídeo do YouTube e imagens (ordem + uma principal). Salvar = publicar (sem rascunho).
 */
class ArtworkWriter
{
    private const TRANSLATABLE = [
        'title', 'description', 'dimensions_note',
        'seo_title', 'seo_description', 'og_title', 'og_description',
    ];

    public function __construct(private Slugger $slugger) {}

    public function save(?Artwork $artwork, array $data): Artwork
    {
        return DB::transaction(function () use ($artwork, $data) {
            $artwork ??= new Artwork();

            $attributes = Arr::only($data, [
                'year', 'height_cm', 'width_cm', 'depth_cm', 'category_id', 'technique_id', 'series_id',
                'status', 'price_brl', 'price_usd', 'show_price', 'sort_order',
            ]);
            if (array_key_exists('video_url', $data)) {
                $attributes['youtube_video_id'] = $this->video($data['video_url']);
            }
            if (isset($attributes['status']) && $artwork->status?->value !== $attributes['status']) {
                $attributes['status_changed_at'] = now();
            }

            $artwork->fill($attributes)->save();

            foreach ($data['translations'] ?? [] as $locale => $values) {
                $this->saveTranslation($artwork, $locale, (array) $values);
            }

            if (array_key_exists('images', $data)) {
                $this->syncImages($artwork, $data['images']);
            }

            return $artwork->fresh(['translations', 'images.media.variants', 'video']);
        });
    }

    public function setStatus(Artwork $artwork, ArtworkStatus $status): Artwork
    {
        if ($artwork->status !== $status) {
            $artwork->forceFill(['status' => $status, 'status_changed_at' => now()])->save();
        }

        return $artwork;
    }

    public function delete(Artwork $artwork): void
    {
        DB::transaction(function () use ($artwork) {
            // artwork_images usa RESTRICT (coluna gerada da imagem principal): apagar antes
            ArtworkImage::where('artwork_id', $artwork->getKey())->delete();
            $artwork->delete();
        });
    }

    private function saveTranslation(Artwork $artwork, string $locale, array $values): void
    {
        $title = trim((string) ($values['title'] ?? ''));
        if ($title === '') {
            return; // sem título não há tradução (o idioma cai para o de origem)
        }

        $fields = Arr::only($values, self::TRANSLATABLE);
        $fields['title'] = $title;
        $fields['slug'] = $this->slugger->unique(
            'artwork_translations', 'artwork_id', $artwork->getKey(), $locale,
            trim((string) ($values['slug'] ?? '')) ?: $title, 220,
        );

        $artwork->saveTranslation($locale, $fields, 'manual');
    }

    /** @param array<int, array{media_file_id:int, is_primary?:bool}> $images na ordem desejada */
    private function syncImages(Artwork $artwork, array $images): void
    {
        ArtworkImage::where('artwork_id', $artwork->getKey())->delete();

        $primaryIndex = collect($images)->search(fn ($i) => ! empty($i['is_primary']));
        $primaryIndex = $primaryIndex === false ? 0 : $primaryIndex;

        foreach (array_values($images) as $index => $image) {
            ArtworkImage::create([
                'artwork_id' => $artwork->getKey(),
                'media_file_id' => $image['media_file_id'],
                'sort_order' => $index,
                'is_primary' => $index === $primaryIndex,
            ]);
        }
    }

    private function video(?string $url): ?int
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $id = YoutubeVideo::parseId($url);

        return YoutubeVideo::firstOrCreate(['youtube_id' => $id], ['original_url' => $url])->getKey();
    }

    public static function locales(): array
    {
        return Locale::ALL;
    }
}
