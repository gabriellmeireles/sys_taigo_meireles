<?php

namespace App\Domain\Admins;

use App\Models\Admin;
use App\Notifications\AdminInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Regras de gestão de administradores (todos com as mesmas permissões):
 *  - ninguém desativa ou exclui a própria conta;
 *  - sempre resta pelo menos um administrador que consegue entrar;
 *  - novo administrador recebe convite por e-mail para definir a senha.
 */
class AdminAccounts
{
    public function invite(string $name, string $email, ?Admin $invitedBy = null): Admin
    {
        $admin = Admin::create([
            'name' => $name,
            'email' => mb_strtolower($email),
            'is_active' => true,
        ]);

        $this->sendInvitation($admin, $invitedBy);

        return $admin;
    }

    public function sendInvitation(Admin $admin, ?Admin $invitedBy = null): void
    {
        if (! $admin->isInvited()) {
            throw ValidationException::withMessages([
                'admin' => 'Este administrador já definiu a senha.',
            ]);
        }

        $token = Password::broker('invites')->createToken($admin);
        $admin->notify(new AdminInvitationNotification($token, $invitedBy?->name));
    }

    public function setActive(Admin $admin, bool $active, Admin $actor): Admin
    {
        if (! $active) {
            $this->guardRemoval($admin, $actor, 'desativar');
        }

        $admin->forceFill(['is_active' => $active])->save();

        if (! $active) {
            $admin->endSessions();
        }

        return $admin;
    }

    public function delete(Admin $admin, Admin $actor): void
    {
        $this->guardRemoval($admin, $actor, 'excluir');

        DB::transaction(function () use ($admin) {
            $admin->endSessions();
            DB::table('password_reset_tokens')->where('email', $admin->email)->delete();
            DB::table('admin_invite_tokens')->where('email', $admin->email)->delete();
            $admin->delete();
        });
    }

    private function guardRemoval(Admin $admin, Admin $actor, string $verb): void
    {
        if ($admin->is($actor)) {
            throw ValidationException::withMessages([
                'admin' => "Você não pode {$verb} a sua própria conta.",
            ]);
        }

        $othersUsable = Admin::usable()->whereKeyNot($admin->getKey())->count();

        if ($othersUsable === 0) {
            throw ValidationException::withMessages([
                'admin' => "Não é possível {$verb} o último administrador ativo.",
            ]);
        }
    }
}
