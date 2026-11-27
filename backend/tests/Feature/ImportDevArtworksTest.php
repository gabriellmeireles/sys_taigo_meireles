<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Concerns\InteractsWithCatalog;
use Tests\TestCase;

class ImportDevArtworksTest extends TestCase
{
    use InteractsWithCatalog, RefreshDatabase;

    public function test_importa_pasta_de_series_a_partir_do_nome_dos_arquivos(): void
    {
        $this->fakeDisks();
        $root = storage_path('framework/testing/import-'.uniqid());
        File::ensureDirectoryExists("{$root}/ecra");
        File::ensureDirectoryExists("{$root}/literatura");
        // Manter as referências: o arquivo temporário some quando o objeto é descartado
        $a = UploadedFile::fake()->image('a.jpg', 300, 400);
        $b = UploadedFile::fake()->image('b.jpg', 400, 300);
        $c = UploadedFile::fake()->image('c.jpg', 10, 10);
        copy($a->getRealPath(), "{$root}/ecra/AS FACES DE REMBRANT - 162x130cm - 2019.jpg");
        copy($b->getRealPath(), "{$root}/literatura/ODISSEU - 130x162cm - 2020.jpg");
        copy($c->getRealPath(), "{$root}/literatura/sem-padrao.jpg");

        $this->artisan('dev:import-artworks', ['path' => $root])
            ->expectsOutputToContain('Nome fora do padrão, ignorado: sem-padrao.jpg')
            ->expectsOutputToContain('2 obra(s) importada(s).')
            ->assertSuccessful();

        $rembrandt = Artwork::whereHas('translations', fn ($q) => $q->where('title', 'As Faces de Rembrandt'))->firstOrFail();
        $this->assertSame(2019, $rembrandt->year);
        $this->assertEquals(162, $rembrandt->height_cm);
        $this->assertEquals(130, $rembrandt->width_cm);
        $this->assertSame('Ecrã', $rembrandt->series->name(Locale::SOURCE));
        $this->assertSame('Óleo sobre tela', $rembrandt->technique->name(Locale::SOURCE));
        $this->assertSame('Oil on canvas', $rembrandt->technique->name('en'));
        $this->assertStringContainsString('Dados de teste', $rembrandt->translation()->description);
        $this->assertTrue($rembrandt->primaryImage->media->processing_status === 'ready');

        // Rodar de novo não duplica
        $this->artisan('dev:import-artworks', ['path' => $root])->expectsOutputToContain('0 obra(s) importada(s).');

        File::deleteDirectory($root);
    }
}
