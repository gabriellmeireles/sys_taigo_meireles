<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Administrador do painel. Todos têm as mesmas permissões (sem papéis, sem 2FA).
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
}
