<?php

namespace Tests\Feature\Admin;

use App\Notifications\AdminInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmins;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use InteractsWithAdmins, RefreshDatabase;

    private const URL = '/api/v1/admin/admins';

    public function test_lista_administradores_com_situacao(): void
    {
        $eu = $this->makeAdmin(['name' => 'Ana']);
        $this->makeAdmin(['name' => 'Bruno', 'password_hash' => null]);
        $this->makeAdmin(['name' => 'Carla', 'is_active' => false]);
        $this->actingAs($eu, 'web');

        $this->fromPanel()->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Ana')
            ->assertJsonPath('data.0.is_current', true)
            ->assertJsonPath('data.1.status', 'invited')
            ->assertJsonPath('data.2.status', 'inactive');
    }

    public function test_convida_novo_administrador_por_email(): void
    {
        Notification::fake();
        $this->actingAs($this->makeAdmin(['name' => 'Ana']), 'web');

        $resp = $this->fromPanel()->postJson(self::URL, ['name' => 'Novo Admin', 'email' => 'Novo@Teste.test'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'invited')
            ->assertJsonPath('data.email', 'novo@teste.test');

        $this->assertDatabaseHas('admin_invite_tokens', ['email' => 'novo@teste.test']);
        Notification::assertSentTo(
            \App\Models\Admin::find($resp->json('data.id')),
            AdminInvitationNotification::class,
            fn ($n) => $n->invitedBy === 'Ana',
        );

        $this->fromPanel()->postJson(self::URL, ['name' => 'Repetido', 'email' => 'novo@teste.test'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Já existe um administrador com este e-mail.');
    }

    public function test_nao_pode_desativar_nem_excluir_a_si_mesmo(): void
    {
        $eu = $this->makeAdmin();
        $this->makeAdmin();
        $this->actingAs($eu, 'web');

        $this->fromPanel()->patchJson(self::URL."/{$eu->id}", ['is_active' => false])
            ->assertStatus(422)->assertJsonPath('errors.admin.0', 'Você não pode desativar a sua própria conta.');

        $this->fromPanel()->deleteJson(self::URL."/{$eu->id}")
            ->assertStatus(422)->assertJsonPath('errors.admin.0', 'Você não pode excluir a sua própria conta.');
    }

    public function test_desativar_outro_administrador_encerra_as_sessoes_dele(): void
    {
        $eu = $this->makeAdmin();
        $outro = $this->makeAdmin();
        DB::table('sessions')->insert(['id' => 'sessao-do-outro', 'user_id' => $outro->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($eu, 'web');

        $this->fromPanel()->patchJson(self::URL."/{$outro->id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseMissing('sessions', ['id' => 'sessao-do-outro']);

        $this->fromPanel()->patchJson(self::URL."/{$outro->id}", ['is_active' => true])
            ->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_exclui_outro_administrador_e_seus_tokens(): void
    {
        $eu = $this->makeAdmin();
        $outro = $this->makeAdmin();
        DB::table('password_reset_tokens')->insert(['email' => $outro->email, 'token' => 'x', 'created_at' => now()]);
        $this->actingAs($eu, 'web');

        $this->fromPanel()->deleteJson(self::URL."/{$outro->id}")->assertNoContent();

        $this->assertDatabaseMissing('admins', ['id' => $outro->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $outro->email]);
    }

    public function test_reenvia_convite_apenas_para_quem_ainda_nao_definiu_a_senha(): void
    {
        Notification::fake();
        $eu = $this->makeAdmin();
        $convidado = $this->makeAdmin(['password_hash' => null]);
        $this->actingAs($eu, 'web');

        $this->fromPanel()->postJson(self::URL."/{$convidado->id}/resend-invite")->assertOk();
        Notification::assertSentTo($convidado, AdminInvitationNotification::class);

        $this->fromPanel()->postJson(self::URL."/{$eu->id}/resend-invite")
            ->assertStatus(422)->assertJsonPath('errors.admin.0', 'Este administrador já definiu a senha.');
    }
}
