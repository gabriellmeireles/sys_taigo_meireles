<?php

namespace Tests\Feature\Admin;

use App\Models\Artwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithAdmins;
use Tests\Concerns\InteractsWithCatalog;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use InteractsWithAdmins, InteractsWithCatalog, RefreshDatabase;

    private array $tax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeDisks();
        $this->actingAs($this->makeAdmin(), 'web');
        $this->tax = $this->makeTaxonomies();
    }

    private function create(array $overrides = []): array
    {
        $img = $this->makeImage(400, 500);

        return $this->fromPanel()->postJson('/api/v1/admin/artworks', $this->artworkPayload($this->tax, [$img->id], array_replace_recursive([
            'translations' => ['pt-BR' => ['title' => 'Tigre I', 'description' => 'Óleo sobre tela.']],
        ], $overrides)))->assertCreated()->json('data');
    }

    private function translation(int $id, string $locale): ?object
    {
        return DB::table('artwork_translations')->where('artwork_id', $id)->where('locale_code', $locale)->first();
    }

    public function test_salvar_em_portugues_gera_traducoes_automaticas_em_ingles_e_espanhol(): void
    {
        $id = $this->create()['id'];

        $en = $this->translation($id, 'en');
        $this->assertSame('[en] Tigre I', $en->title);
        $this->assertSame('[en] Óleo sobre tela.', $en->description);
        $this->assertSame('en-tigre-i', $en->slug);
        $this->assertSame('auto', $en->translation_status);
        $this->assertSame('[es] Tigre I', $this->translation($id, 'es')->title);

        $this->fromPanel()->getJson("/api/v1/admin/artworks/{$id}")
            ->assertJsonPath('data.translation_states.en', 'auto')
            ->assertJsonPath('data.translation_states.es', 'auto');
    }

    public function test_mudar_o_portugues_retraduz_as_automaticas_e_marca_revisadas_como_desatualizadas(): void
    {
        $data = $this->create();
        $id = $data['id'];

        // Revisão manual do inglês
        $payload = $this->artworkPayload($this->tax, [$data['images'][0]['media_file_id']], [
            'translations' => [
                'pt-BR' => ['title' => 'Tigre I', 'description' => 'Óleo sobre tela.'],
                'en' => ['title' => 'Tiger I', 'description' => 'Oil on canvas.'],
            ],
        ]);
        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $payload)->assertOk()
            ->assertJsonPath('data.translation_states.en', 'manual');
        $this->assertNotNull($this->translation($id, 'en')->reviewed_at);

        // Muda o português
        $payload['translations']['pt-BR']['title'] = 'Tigre Primeiro';
        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $payload)->assertOk()
            ->assertJsonPath('data.translation_states.en', 'outdated')
            ->assertJsonPath('data.translation_states.es', 'auto');

        $this->assertSame('Tiger I', $this->translation($id, 'en')->title);          // revisada não é sobrescrita
        $this->assertSame('[es] Tigre Primeiro', $this->translation($id, 'es')->title); // automática acompanha
    }

    public function test_reenviar_traducao_sem_mudar_nada_nao_a_marca_como_revisada(): void
    {
        $data = $this->create();
        $id = $data['id'];
        $en = $this->translation($id, 'en');

        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $this->artworkPayload($this->tax, [$data['images'][0]['media_file_id']], [
            'translations' => [
                'pt-BR' => ['title' => 'Tigre I', 'description' => 'Óleo sobre tela.'],
                'en' => ['title' => $en->title, 'slug' => $en->slug, 'description' => $en->description],
            ],
        ]))->assertOk()->assertJsonPath('data.translation_states.en', 'auto');

        // Nada mudou no português: não chama o tradutor de novo
        $this->assertCount(2, $this->translator->calls);
    }

    public function test_editar_traducao_desatualizada_a_torna_revisada_de_novo(): void
    {
        $data = $this->create();
        $id = $data['id'];
        $media = $data['images'][0]['media_file_id'];
        $pt = ['title' => 'Tigre I', 'description' => 'Óleo sobre tela.'];

        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $this->artworkPayload($this->tax, [$media], ['translations' => ['pt-BR' => $pt, 'en' => ['title' => 'Tiger I']]]));
        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $this->artworkPayload($this->tax, [$media], ['translations' => ['pt-BR' => ['title' => 'Tigre Um'] + $pt]]))
            ->assertJsonPath('data.translation_states.en', 'outdated');

        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $this->artworkPayload($this->tax, [$media], [
            'translations' => ['pt-BR' => ['title' => 'Tigre Um'] + $pt, 'en' => ['title' => 'Tiger One']],
        ]))->assertJsonPath('data.translation_states.en', 'manual');
    }

    public function test_retraduzir_automaticamente_sobrescreve_a_revisada(): void
    {
        $data = $this->create();
        $id = $data['id'];
        $this->fromPanel()->putJson("/api/v1/admin/artworks/{$id}", $this->artworkPayload($this->tax, [$data['images'][0]['media_file_id']], [
            'translations' => ['pt-BR' => ['title' => 'Tigre I'], 'en' => ['title' => 'Tiger I']],
        ]));

        $this->fromPanel()->postJson("/api/v1/admin/translations/artwork/{$id}/en/auto")->assertOk()
            ->assertJsonPath('data.translation_status', 'auto')
            ->assertJsonPath('data.title', '[en] Tigre I');
    }

    public function test_falha_do_tradutor_mantem_o_idioma_sem_traducao_e_avisa(): void
    {
        $this->translator->fail = true;
        $id = $this->create()['id']; // a obra é salva mesmo com o tradutor fora do ar

        $this->assertNull($this->translation($id, 'en'));
        $this->assertSame('error', DB::table('integration_states')->where('integration_key', 'translation')->value('status'));

        $this->fromPanel()->postJson("/api/v1/admin/translations/artwork/{$id}/en/auto")
            ->assertStatus(503)
            ->assertJsonPath('message', 'A tradução automática não respondeu agora. Tente de novo em instantes ou traduza à mão.');
    }

    public function test_central_lista_estados_e_progresso(): void
    {
        $a = $this->create()['id'];
        $b = $this->create(['translations' => ['pt-BR' => ['title' => 'Odisseu']]])['id'];
        DB::table('artwork_translations')->where('artwork_id', $b)->where('locale_code', 'en')->delete();

        $this->fromPanel()->getJson('/api/v1/admin/translations?locale=en&type=artwork')->assertOk()
            ->assertJsonCount(2, 'data');
        $this->fromPanel()->getJson('/api/v1/admin/translations?locale=en&type=artwork&state=missing')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Odisseu');
        // Séries, técnicas e categorias também aparecem (nomes traduzidos à mão)
        $this->fromPanel()->getJson('/api/v1/admin/translations?locale=es&type=series')
            ->assertJsonPath('data.0.title', 'Ecrã')
            ->assertJsonPath('data.0.state', 'missing')
            ->assertJsonPath('data.0.auto', false);

        $progress = $this->fromPanel()->getJson('/api/v1/admin/translations/progress')->assertOk()->json('data.en');
        $this->assertSame(1, $progress['auto']);
        $this->assertSame(1 + 3, $progress['missing']); // Odisseu + série, técnica, categoria
        $this->assertSame(5, $progress['total']);
        $this->assertNotNull($a);
    }

    public function test_series_nao_recebem_traducao_automatica_mas_ficam_desatualizadas(): void
    {
        $id = $this->fromPanel()->postJson('/api/v1/admin/series', ['translations' => ['pt-BR' => ['name' => 'Ecrã II'], 'en' => ['name' => 'Screen II']]])
            ->assertCreated()->json('data.id');
        $this->assertSame([], $this->translator->calls);

        $this->fromPanel()->putJson("/api/v1/admin/series/{$id}", ['translations' => ['pt-BR' => ['name' => 'Ecrã 2'], 'en' => ['name' => 'Screen II']]])
            ->assertOk()
            ->assertJsonPath('data.translation_states.en', 'outdated')
            ->assertJsonPath('data.translation_states.es', 'missing');
    }

    public function test_revisada_sem_referencia_de_origem_nao_fica_desatualizada(): void
    {
        $id = $this->create()['id'];
        DB::table('artwork_translations')->where('artwork_id', $id)->where('locale_code', 'en')
            ->update(['title' => 'Tiger I', 'translation_status' => 'manual', 'source_hash' => null]);

        $this->artisan('translations:sync')->assertSuccessful();

        $en = $this->translation($id, 'en');
        $this->assertSame(0, (int) $en->is_outdated);
        $this->assertNotNull($en->source_hash);
        $this->assertSame('Tiger I', $en->title);
    }

    public function test_comando_agenda_traducoes_pendentes(): void
    {
        $id = $this->create()['id'];
        DB::table('artwork_translations')->where('artwork_id', $id)->where('locale_code', '!=', 'pt-BR')->delete();

        $this->artisan('translations:sync')->expectsOutputToContain('1 item(ns) verificados')->assertSuccessful();
        $this->assertSame('auto', $this->translation($id, 'es')->translation_status);
        $this->assertInstanceOf(Artwork::class, Artwork::find($id));
    }
}
