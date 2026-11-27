<?php

namespace Tests\Feature\Admin;

use App\Domain\Admins\AdminAccounts;
use App\Notifications\AdminInvitationNotification;
use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\InteractsWithAdmins;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InteractsWithAdmins, RefreshDatabase;

    private const LOGIN = '/api/v1/admin/auth/login';

    public function test_admin_ativo_entra_e_ve_seus_dados(): void
    {
        $admin = $this->makeAdmin();

        $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonPath('data.status', 'active');

        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->fromPanel()->getJson('/api/v1/admin/auth/me')->assertOk()->assertJsonPath('data.id', $admin->id);
    }

    public function test_email_em_maiusculas_tambem_entra(): void
    {
        $admin = $this->makeAdmin();

        $this->fromPanel()->postJson(self::LOGIN, ['email' => strtoupper($admin->email), 'password' => self::PASSWORD])
            ->assertOk();
    }

    public function test_senha_errada_recebe_mensagem_generica(): void
    {
        $admin = $this->makeAdmin();

        $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => 'errada-123456'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'E-mail ou senha incorretos.');
    }

    public function test_admin_inativo_ou_convidado_nao_entra(): void
    {
        $inativo = $this->makeAdmin(['is_active' => false]);
        $convidado = $this->makeAdmin(['password_hash' => null]);

        foreach ([$inativo, $convidado] as $admin) {
            $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => self::PASSWORD])
                ->assertStatus(422)
                ->assertJsonPath('errors.email.0', 'E-mail ou senha incorretos.');
        }
    }

    public function test_bloqueia_apos_cinco_tentativas_erradas(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => 'errada-123456'])->assertStatus(422);
        }

        $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertStatus(429);
    }

    public function test_rotas_protegidas_exigem_login(): void
    {
        $this->fromPanel()->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
        $this->fromPanel()->getJson('/api/v1/admin/admins')->assertUnauthorized();
    }

    public function test_logout_encerra_a_sessao(): void
    {
        $admin = $this->makeAdmin();
        $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => self::PASSWORD])->assertOk();

        $this->fromPanel()->postJson('/api/v1/admin/auth/logout')->assertNoContent();

        // Numa requisição real cada chamada começa do zero; no teste, o guard do Sanctum
        // guardaria o usuário já resolvido. Limpamos para simular a próxima requisição.
        $this->app['auth']->forgetGuards();
        $this->fromPanel()->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
    }

    public function test_admin_desativado_perde_a_sessao_na_proxima_requisicao(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'web');

        $admin->forceFill(['is_active' => false])->save();

        $this->fromPanel()->getJson('/api/v1/admin/auth/me')->assertUnauthorized();
    }

    public function test_esqueci_a_senha_responde_igual_para_email_existente_ou_nao(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();

        $existe = $this->fromPanel()->postJson('/api/v1/admin/auth/forgot-password', ['email' => $admin->email])->assertOk();
        $naoExiste = $this->fromPanel()->postJson('/api/v1/admin/auth/forgot-password', ['email' => 'ninguem@teste.test'])->assertOk();

        $this->assertSame($existe->json('message'), $naoExiste->json('message'));
        Notification::assertSentTo($admin, AdminResetPasswordNotification::class, function ($n) use ($admin) {
            return str_starts_with($n->url($admin), config('platform.admin_url').'/redefinir-senha?token=');
        });
        Notification::assertSentTimes(AdminResetPasswordNotification::class, 1);
    }

    public function test_redefine_a_senha_com_link_valido_uma_unica_vez(): void
    {
        $admin = $this->makeAdmin();
        DB::table('sessions')->insert(['id' => 'sessao-antiga', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);
        $token = Password::broker('admins')->createToken($admin);
        $dados = ['token' => $token, 'email' => $admin->email, 'password' => 'nova-senha-segura', 'password_confirmation' => 'nova-senha-segura'];

        $this->fromPanel()->postJson('/api/v1/admin/auth/reset-password', $dados)->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'sessao-antiga']);
        $this->fromPanel()->postJson(self::LOGIN, ['email' => $admin->email, 'password' => 'nova-senha-segura'])->assertOk();

        // O mesmo link não funciona de novo
        $this->fromPanel()->postJson('/api/v1/admin/auth/reset-password', $dados)
            ->assertStatus(422)
            ->assertJsonPath('errors.token.0', 'Este link é inválido ou expirou. Solicite um novo.');
    }

    public function test_senha_nova_precisa_de_12_caracteres_e_confirmacao(): void
    {
        $admin = $this->makeAdmin();
        $token = Password::broker('admins')->createToken($admin);

        $this->fromPanel()->postJson('/api/v1/admin/auth/reset-password', [
            'token' => $token, 'email' => $admin->email, 'password' => 'curta', 'password_confirmation' => 'outra',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_convidado_define_a_senha_pelo_convite_e_entra(): void
    {
        Notification::fake();
        $convidado = app(AdminAccounts::class)->invite('Nova Pessoa', 'Nova@Teste.test');

        $token = null;
        Notification::assertSentTo($convidado, AdminInvitationNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->assertSame('nova@teste.test', $convidado->email);

        $this->fromPanel()->postJson('/api/v1/admin/auth/accept-invite', [
            'token' => $token, 'email' => 'nova@teste.test', 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova',
        ])->assertOk();

        $this->fromPanel()->postJson(self::LOGIN, ['email' => 'nova@teste.test', 'password' => 'minha-senha-nova'])
            ->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_token_de_redefinicao_nao_serve_como_convite(): void
    {
        $admin = $this->makeAdmin(['password_hash' => null]);
        $token = Password::broker('admins')->createToken($admin);

        $this->fromPanel()->postJson('/api/v1/admin/auth/accept-invite', [
            'token' => $token, 'email' => $admin->email, 'password' => 'minha-senha-nova', 'password_confirmation' => 'minha-senha-nova',
        ])->assertStatus(422);
    }

    public function test_troca_a_propria_senha_com_a_senha_atual(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'web');

        $this->fromPanel()->putJson('/api/v1/admin/auth/password', [
            'current_password' => 'errada-123456', 'password' => 'outra-senha-segura', 'password_confirmation' => 'outra-senha-segura',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', 'A senha atual está incorreta.');

        $this->fromPanel()->putJson('/api/v1/admin/auth/password', [
            'current_password' => self::PASSWORD, 'password' => 'outra-senha-segura', 'password_confirmation' => 'outra-senha-segura',
        ])->assertOk();

        $this->assertNotNull($admin->fresh()->password_changed_at);
    }

    public function test_atualiza_nome_e_email_da_propria_conta(): void
    {
        $admin = $this->makeAdmin();
        $outro = $this->makeAdmin();
        $this->actingAs($admin, 'web');

        $this->fromPanel()->patchJson('/api/v1/admin/auth/me', ['email' => $outro->email])->assertStatus(422);
        $this->fromPanel()->patchJson('/api/v1/admin/auth/me', ['name' => 'Nome Novo', 'email' => 'NOVO@teste.test'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome Novo')
            ->assertJsonPath('data.email', 'novo@teste.test');
    }
}
