<?php

namespace Tests\Feature\Admin;

use App\Models\MediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmins;
use Tests\Concerns\InteractsWithCatalog;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use InteractsWithAdmins, InteractsWithCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeDisks();
        $this->actingAs($this->makeAdmin(), 'web');
    }

    public function test_envia_imagem_guarda_o_original_e_gera_as_versoes(): void
    {
        $file = UploadedFile::fake()->image('Tigre I.jpg', 1200, 1500);

        $resp = $this->fromPanel()->post('/api/v1/admin/media', ['file' => $file])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ready') // fila síncrona nos testes
            ->assertJsonPath('data.original_filename', 'Tigre I.jpg')
            ->assertJsonPath('data.width', 1200);

        $media = MediaFile::findOrFail($resp->json('data.id'));
        Storage::disk('local')->assertExists($media->original_path);
        $this->assertStringStartsWith('media/originals/', $media->original_path);

        // 480, 960 e 1200 (não amplia além do original) em WebP e JPEG + og
        $variants = collect($resp->json('data.variants'));
        $this->assertCount(7, $variants);
        $this->assertEqualsCanonicalizing([480, 960, 1200], $variants->where('format', 'webp')->pluck('width')->all());
        $og = $variants->firstWhere('variant', 'og');
        $this->assertSame([1200, 630], [$og['width'], $og['height']]);
        foreach ($variants as $v) {
            Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($v['url'], PHP_URL_PATH)));
        }
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $media->dominant_color);
    }

    public function test_recusa_arquivo_que_nao_e_imagem(): void
    {
        $file = UploadedFile::fake()->createWithContent('obra.jpg', 'isto não é uma imagem');

        $this->fromPanel()->post('/api/v1/admin/media', ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_recusa_imagem_acima_do_limite(): void
    {
        config(['platform.media_max_upload_mb' => 1]);
        $file = UploadedFile::fake()->image('grande.jpg', 100, 100)->size(1500);

        $this->fromPanel()->post('/api/v1/admin/media', ['file' => $file])
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'A imagem passa do limite de 1 MB.');
    }

    public function test_baixa_o_original(): void
    {
        $media = $this->makeImage(600, 800);

        $this->get("/api/v1/admin/media/{$media->id}/original")
            ->assertOk()
            ->assertDownload('obra.jpg');
    }
}
