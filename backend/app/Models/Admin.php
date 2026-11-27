<?php

namespace App\Models;

use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/**
 * Administrador do painel. Todos têm as mesmas permissões (sem papéis, sem 2FA).
 *
 * Situações:
 *  - convidado: ainda não definiu a senha (password_hash nulo);
 *  - ativo: pode entrar no painel;
 *  - inativo: desativado por outro administrador.
 */
#[Fillable(['name', 'email', 'password_hash', 'is_active'])]
#[Hidden(['password_hash'])]
class Admin extends Authenticatable
{
    use Notifiable;

    /** A senha fica na coluna password_hash. */
    protected $authPasswordName = 'password_hash';

    /** A tabela admins não tem coluna "lembrar-me". */
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /** Administradores que conseguem entrar: ativos e com senha definida. */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('password_hash');
    }

    public function isInvited(): bool
    {
        return $this->password_hash === null;
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'inactive',
            $this->isInvited() => 'invited',
            default => 'active',
        };
    }

    /** Encerra as sessões do administrador (opcionalmente mantendo a sessão atual). */
    public function endSessions(?string $exceptSessionId = null): void
    {
        DB::table('sessions')
            ->where('user_id', $this->getKey())
            ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminResetPasswordNotification($token));
    }
}
