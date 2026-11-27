<?php

namespace App\Console\Commands;

use App\Domain\Catalog\ArtworkWriter;
use App\Domain\Catalog\Slugger;
use App\Domain\Media\MediaLibrary;
use App\Models\Category;
use App\Models\Locale;
use App\Models\Series;
use App\Models\Technique;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa obras de TESTE a partir de pastas de imagens (somente desenvolvimento).
 *
 *   dev-data/series/<pasta-da-série>/<TÍTULO> - <altura>x<largura>cm - <ano>.jpg
 *
 * Usa o mesmo fluxo do painel (MediaLibrary + ArtworkWriter). Status e preços são
 * fictícios e a descrição avisa que são dados de teste.
 */
class ImportDevArtworks extends Command
{
    protected $signature = 'dev:import-artworks {path=../dev-data/series : Pasta com uma subpasta por série}';

    protected $description = 'Importa obras de teste (imagens + dados do nome do arquivo) — só em ambiente local';

    /** Nome exibido de cada pasta de série. */
    private const SERIES_NAMES = ['ecra' => 'Ecrã', 'literatura' => 'Literatura'];

    /** Correções de grafia nos títulos vindos dos nomes de arquivo. */
    private const TITLE_FIXES = ['Rembrant' => 'Rembrandt'];

    private const SMALL_WORDS = ['a', 'o', 'as', 'os', 'de', 'da', 'do', 'das', 'dos', 'e', 'em', 'na', 'no'];

    /** Status de teste em rodízio (disponível, reservada, vendida). */
    private const STATUSES = ['available', 'available', 'reserved', 'sold', 'available', 'sold', 'available', 'reserved', 'available'];

    public function handle(MediaLibrary $library, ArtworkWriter $writer, Slugger $slugger): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Disponível apenas em desenvolvimento.');

            return self::FAILURE;
        }

        $root = realpath(base_path($this->argument('path'))) ?: realpath($this->argument('path'));
        if (! $root || ! is_dir($root)) {
            $this->error("Pasta não encontrada: {$this->argument('path')}");

            return self::FAILURE;
        }

        $category = $this->taxonomy(Category::class, ['pt-BR' => 'Pintura', 'en' => 'Painting', 'es' => 'Pintura'], $slugger, 'category_translations', 'category_id');
        $technique = $this->taxonomy(Technique::class, ['pt-BR' => 'Óleo sobre tela', 'en' => 'Oil on canvas', 'es' => 'Óleo sobre lienzo'], $slugger, 'technique_translations', 'technique_id');

        $index = 0;
        $created = 0;
        foreach (glob($root.'/*', GLOB_ONLYDIR) as $dir) {
            $folder = basename($dir);
            $seriesName = self::SERIES_NAMES[Str::lower($folder)] ?? Str::title($folder);
            $series = $this->taxonomy(Series::class, ['pt-BR' => $seriesName], $slugger, 'series_translations', 'series_id');

            foreach (glob($dir.'/*.{jpg,jpeg,png,tif,tiff,webp,JPG,JPEG,PNG,TIF,TIFF,WEBP}', GLOB_BRACE) as $file) {
                $parsed = $this->parse(pathinfo($file, PATHINFO_FILENAME));
                if (! $parsed) {
                    $this->warn('Nome fora do padrão, ignorado: '.basename($file));
                    continue;
                }

                $exists = DB::table('artwork_translations')
                    ->where('locale_code', Locale::SOURCE)->where('title', $parsed['title'])->exists();
                if ($exists) {
                    $this->line("  já existe: {$parsed['title']}");
                    $index++;
                    continue;
                }

                $media = $library->storeImage($file, basename($file), null, processNow: true);
                $status = self::STATUSES[$index % count(self::STATUSES)];
                [$brl, $usd] = $this->fakePrices($parsed['height'], $parsed['width']);

                $writer->save(null, [
                    'year' => $parsed['year'],
                    'height_cm' => $parsed['height'],
                    'width_cm' => $parsed['width'],
                    'category_id' => $category->id,
                    'technique_id' => $technique->id,
                    'series_id' => $series->id,
                    'status' => $status,
                    'show_price' => $index % 3 !== 2,
                    'price_brl' => $brl,
                    'price_usd' => $usd,
                    'images' => [['media_file_id' => $media->id, 'is_primary' => true]],
                    'translations' => ['pt-BR' => [
                        'title' => $parsed['title'],
                        'description' => 'Dados de teste: obra importada para o protótipo. Status e preço são fictícios.',
                    ]],
                ]);

                $this->info(sprintf('  %-28s %s · %sx%s cm · %s · %s', $parsed['title'], $seriesName, $parsed['height'], $parsed['width'], $parsed['year'], $status));
                $index++;
                $created++;
            }
        }

        $this->info("{$created} obra(s) importada(s).");

        return self::SUCCESS;
    }

    /** "TIGRE I - 200x150cm - 2019" → título, altura, largura, ano. */
    private function parse(string $name): ?array
    {
        if (! preg_match('/^(.+?)\s*-\s*(\d+(?:[.,]\d+)?)\s*x\s*(\d+(?:[.,]\d+)?)\s*cm\s*-\s*(\d{4})$/iu', trim($name), $m)) {
            return null;
        }

        return [
            'title' => $this->titleCase($m[1]),
            'height' => (float) str_replace(',', '.', $m[2]),
            'width' => (float) str_replace(',', '.', $m[3]),
            'year' => (int) $m[4],
        ];
    }

    private function titleCase(string $text): string
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($text)));
        foreach ($words as $i => $word) {
            if (preg_match('/^(i|ii|iii|iv|v|vi|vii|viii|ix|x)$/', $word)) {
                $words[$i] = mb_strtoupper($word);
            } elseif ($i > 0 && in_array($word, self::SMALL_WORDS, true)) {
                $words[$i] = $word;
            } else {
                $words[$i] = mb_strtoupper(mb_substr($word, 0, 1)).mb_substr($word, 1);
            }
        }

        return strtr(implode(' ', $words), self::TITLE_FIXES);
    }

    /** Preços fictícios proporcionais à área (só para testar a exibição). */
    private function fakePrices(float $h, float $w): array
    {
        $brl = max(3000, round($h * $w * 1.1 / 500) * 500);

        return [$brl, max(600, round($brl / 5.6 / 100) * 100)];
    }

    private function taxonomy(string $model, array $names, Slugger $slugger, string $table, string $fk)
    {
        $table_ = DB::table($table)->where('locale_code', Locale::SOURCE)->where('name', $names[Locale::SOURCE])->first();
        $item = $table_ ? $model::find($table_->{$fk}) : $model::create(['sort_order' => (int) $model::max('sort_order') + 1]);

        foreach ($names as $locale => $name) {
            $item->saveTranslation($locale, [
                'name' => $name,
                'slug' => $slugger->unique($table, $fk, $item->id, $locale, $name, 180),
            ], 'manual');
        }

        return $item;
    }
}
