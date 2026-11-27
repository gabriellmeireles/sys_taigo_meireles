<?php

namespace Tests\Feature\Admin;

use App\Models\Artwork;
use App\Models\ArtworkImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmins;
use Tests\Concerns\InteractsWithCatalog;
use Tests\TestCase;

class ArtworkTest extends TestCase
{
    use InteractsWithAdmins, InteractsWithCatalog, RefreshDatabase;

    private const URL = '/api/v1/admin/artworks';

    private array $tax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeDisks();
        $this->actingAs($this->makeAdmin(), 'web');
        $this->tax = $this->makeTaxonomies();
    }

    public function test_cadastra_obra_com_imagens_slug_e_principal(): void
    {
        $a = $this->makeImage();
        $b = $this->makeImage(800, 600);

        $resp = $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id, $b->id], [
            'images' => [1 => ['is_primary' => true]],
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ?t=10',
            'translations' => ['pt-BR' => ['title' => 'As Faces de Rembrandt']],
        ]))->assertCreated();

        $resp->assertJsonPath('data.translations.pt-BR.slug', 'as-faces-de-rembrandt')
            ->assertJsonPath('data.images.0.media_file_id', $a->id)
            ->assertJsonPath('data.images.0.is_primary', false)
            ->assertJsonPath('data.images.1.is_primary', true)
            ->assertJsonPath('data.video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->assertJsonPath('data.translation_states.pt-BR', 'manual')
            ->assertJsonPath('data.translation_states.en', 'missing');

        // Mesmo título → slug diferente no mesmo idioma
        $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id], [
            'translations' => ['pt-BR' => ['title' => 'As Faces de Rembrandt']],
        ]))->assertCreated()->assertJsonPath('data.translations.pt-BR.slug', 'as-faces-de-rembrandt-2');
    }

    public function test_sem_principal_marcada_a_primeira_imagem_vira_principal(): void
    {
        $a = $this->makeImage();
        $id = $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id]))->json('data.id');

        $this->assertTrue(ArtworkImage::where('artwork_id', $id)->first()->is_primary);
    }

    public function test_valida_campos_obrigatorios_e_regras(): void
    {
        $a = $this->makeImage();
        $b = $this->makeImage();

        $this->fromPanel()->postJson(self::URL, ['translations' => ['pt-BR' => ['title' => '']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['translations.pt-BR.title', 'year', 'series_id', 'images', 'status']);

        // P-04: preço obrigatório quando "exibir preço" está ligado
        $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id], ['show_price' => true, 'price_brl' => 18000]))
            ->assertStatus(422)
            ->assertJsonPath('errors.price_usd.0', 'Informe o preço em dólares para exibir o preço no site.');

        // Duas principais
        $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id, $b->id], [
            'images' => [['is_primary' => true], ['is_primary' => true]],
        ]))->assertStatus(422)->assertJsonPath('errors.images.0', 'Escolha apenas uma imagem principal.');

        // Vídeo que não é do YouTube
        $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id], ['video_url' => 'https://vimeo.com/123']))
            ->assertStatus(422)->assertJsonPath('errors.video_url.0', 'Informe um link válido do YouTube.');
    }

    public function test_edita_reordena_imagens_e_registra_mudanca_de_status(): void
    {
        $a = $this->makeImage();
        $b = $this->makeImage();
        $id = $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$a->id, $b->id]))->json('data.id');

        $this->fromPanel()->putJson(self::URL."/{$id}", $this->artworkPayload($this->tax, [$b->id, $a->id], [
            'status' => 'sold',
            'images' => [1 => ['is_primary' => true]],
            'translations' => ['en' => ['title' => 'Odysseus']],
        ]))->assertOk()
            ->assertJsonPath('data.status', 'sold')
            ->assertJsonPath('data.images.0.media_file_id', $b->id)
            ->assertJsonPath('data.images.1.is_primary', true)
            ->assertJsonPath('data.translations.en.slug', 'odysseus');

        $this->assertNotNull(Artwork::find($id)->status_changed_at);
    }

    public function test_lista_com_busca_filtros_e_miniatura(): void
    {
        $img = $this->makeImage();
        foreach (['Tigre I' => 'available', 'Tigre II' => 'sold', 'Odisseu' => 'available'] as $title => $status) {
            $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$img->id], [
                'status' => $status, 'translations' => ['pt-BR' => ['title' => $title]],
            ]))->assertCreated();
        }

        $this->fromPanel()->getJson(self::URL.'?q=tigre')->assertOk()->assertJsonCount(2, 'data');
        $this->fromPanel()->getJson(self::URL.'?q=tigre&status=sold')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tigre II')
            ->assertJsonPath('data.0.series', 'Ecrã');
        $this->assertStringContainsString('/storage/media/', $this->fromPanel()->getJson(self::URL)->json('data.0.thumbnail_url'));
    }

    public function test_troca_status_na_lista_e_exclui(): void
    {
        $img = $this->makeImage();
        $id = $this->fromPanel()->postJson(self::URL, $this->artworkPayload($this->tax, [$img->id]))->json('data.id');

        $this->fromPanel()->patchJson(self::URL."/{$id}/status", ['status' => 'reserved'])
            ->assertOk()->assertJsonPath('data.status', 'reserved');

        $this->fromPanel()->deleteJson(self::URL."/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('artworks', ['id' => $id]);
        $this->assertDatabaseMissing('artwork_images', ['artwork_id' => $id]);
        $this->assertDatabaseHas('media_files', ['id' => $img->id]); // a mídia continua na biblioteca
    }
}
