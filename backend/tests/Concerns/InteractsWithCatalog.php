<?php

namespace Tests\Concerns;

use App\Domain\Media\MediaLibrary;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\Series;
use App\Models\Technique;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait InteractsWithCatalog
{
    protected function fakeDisks(): void
    {
        Storage::fake('local');
        Storage::fake('public');
    }

    /** @return array{category: Category, technique: Technique, series: Series} */
    protected function makeTaxonomies(): array
    {
        $category = Category::create(['sort_order' => 1]);
        $category->saveTranslation('pt-BR', ['name' => 'Pintura', 'slug' => 'pintura']);
        $technique = Technique::create(['sort_order' => 1]);
        $technique->saveTranslation('pt-BR', ['name' => 'Óleo sobre tela', 'slug' => 'oleo-sobre-tela']);
        $series = Series::create(['sort_order' => 1]);
        $series->saveTranslation('pt-BR', ['name' => 'Ecrã', 'slug' => 'ecra']);

        return compact('category', 'technique', 'series');
    }

    protected function makeImage(int $width = 1200, int $height = 1500): MediaFile
    {
        $file = UploadedFile::fake()->image('obra.jpg', $width, $height);

        return app(MediaLibrary::class)->storeUploadedImage($file, null, processNow: true);
    }

    protected function artworkPayload(array $tax, array $mediaIds, array $overrides = []): array
    {
        return array_replace_recursive([
            'year' => 2020,
            'height_cm' => 162,
            'width_cm' => 130,
            'category_id' => $tax['category']->id,
            'technique_id' => $tax['technique']->id,
            'series_id' => $tax['series']->id,
            'status' => 'available',
            'show_price' => false,
            'images' => array_map(fn ($id) => ['media_file_id' => $id], $mediaIds),
            'translations' => ['pt-BR' => ['title' => 'Odisseu']],
        ], $overrides);
    }
}
