<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmins;
use Tests\Concerns\InteractsWithCatalog;
use Tests\TestCase;

class TaxonomyTest extends TestCase
{
    use InteractsWithAdmins, InteractsWithCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->makeAdmin(), 'web');
    }

    public function test_cria_edita_e_lista_series_com_nomes_por_idioma(): void
    {
        $id = $this->fromPanel()->postJson('/api/v1/admin/series', [
            'translations' => ['pt-BR' => ['name' => 'Ecrã'], 'en' => ['name' => 'Screen']],
        ])->assertCreated()
            ->assertJsonPath('data.translations.pt-BR.slug', 'ecra')
            ->assertJsonPath('data.translation_states.es', 'missing')
            ->json('data.id');

        $this->fromPanel()->putJson("/api/v1/admin/series/{$id}", [
            'translations' => ['pt-BR' => ['name' => 'Ecrã'], 'es' => ['name' => 'Pantalla']],
        ])->assertOk()->assertJsonPath('data.translations.es.name', 'Pantalla');

        $this->fromPanel()->getJson('/api/v1/admin/series')->assertOk()
            ->assertJsonPath('data.0.name', 'Ecrã')
            ->assertJsonPath('data.0.artworks_count', 0);
    }

    public function test_nome_em_portugues_e_obrigatorio(): void
    {
        $this->fromPanel()->postJson('/api/v1/admin/techniques', ['translations' => ['en' => ['name' => 'Oil']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['translations.pt-BR.name' => 'Informe o nome em português.']);
    }

    public function test_nao_exclui_taxonomia_em_uso_e_reordena(): void
    {
        $this->fakeDisks();
        $tax = $this->makeTaxonomies();
        $img = $this->makeImage(400, 500);
        $this->fromPanel()->postJson('/api/v1/admin/artworks', $this->artworkPayload($tax, [$img->id]))->assertCreated();

        $this->fromPanel()->deleteJson("/api/v1/admin/techniques/{$tax['technique']->id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.id.0', '1 obra(s) usam esta técnica. Reatribua antes de excluir.');

        $other = $this->fromPanel()->postJson('/api/v1/admin/categories', ['translations' => ['pt-BR' => ['name' => 'Desenho']]])->json('data.id');
        $this->fromPanel()->putJson('/api/v1/admin/categories/order', ['ids' => [$other, $tax['category']->id]])->assertNoContent();
        $this->fromPanel()->getJson('/api/v1/admin/categories')->assertJsonPath('data.0.name', 'Desenho');

        $this->fromPanel()->deleteJson("/api/v1/admin/categories/{$other}")->assertNoContent();
    }
}
