<?php

namespace App\Domain\Catalog;

use App\Domain\Localization\TranslationSync;
use App\Models\Admin;
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

    public function __construct(private Slugger $slugger, private TranslationSync $translations) {}

    public function save(?Artwork $artwork, array $data, ?Admin $by = null): Artwork
    {
        return DB::transaction(function () use ($artwork, $data, $by) {
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

            // Origem primeiro; depois os outros idiomas que a pessoa realmente alterou
            $incoming = $data['translations'] ?? [];
            if (isset($incoming[Locale::SOURCE])) {
                $this->saveTranslation($artwork, Locale::SOURCE, (array) $incoming[Locale::SOURCE]);
            }
            foreach ($incoming as $locale => $values) {
                if ($locale !== Locale::SOURCE && $this->saveTranslation($artwork, $locale, (array) $values)) {
                    $this->translations->markReviewed($artwork, $locale, $by);
                }
            }
            // Idiomas sem tradução ou automáticos são (re)traduzidos; revisados ficam desatualizados
            $this->translations->sourceSaved($artwork);

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

    /** Grava um idioma; devolve false quando nada mudou (mantém o estado da tradução). */
    private function saveTranslation(Artwork $artwork, string $locale, array $values): bool
    {
        $title = trim((string) ($values['title'] ?? ''));
        if ($title === '') {
            return false; // sem título não há tradução (o idioma cai para o de origem)
        }

        $fields = Arr::only($values, self::TRANSLATABLE);
        $fields['title'] = $title;
        $requestedSlug = trim((string) ($values['slug'] ?? ''));

        $existing = DB::table('artwork_translations')
            ->where('artwork_id', $artwork->getKey())->where('locale_code', $locale)->first();
        if ($existing && $this->sameValues($existing, $fields) && ($requestedSlug === '' || $requestedSlug === $existing->slug)) {
            return false;
        }

        $fields['slug'] = $this->slugger->unique(
            'artwork_translations', 'artwork_id', $artwork->getKey(), $locale,
            $requestedSlug ?: ($existing && $existing->title === $title ? $existing->slug : $title), 220,
        );

        $artwork->saveTranslation($locale, $fields, 'manual');

        return true;
    }

    private function sameValues(object $row, array $fields): bool
    {
        foreach ($fields as $field => $value) {
            if (trim((string) ($row->{$field} ?? '')) !== trim((string) ($value ?? ''))) {
                return false;
            }
        }

        return true;
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
