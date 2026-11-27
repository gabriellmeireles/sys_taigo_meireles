<?php

namespace Tests\Concerns;

use App\Models\Admin;

trait InteractsWithAdmins
{
    protected const PASSWORD = 'senha-de-teste-123';

    protected function makeAdmin(array $attributes = []): Admin
    {
        static $n = 0;
        $n++;

        return Admin::create(array_merge([
            'name' => "Admin {$n}",
            'email' => "admin{$n}@teste.test",
            'password_hash' => self::PASSWORD,
            'is_active' => true,
        ], $attributes));
    }

    /** Requisição como se viesse do painel Angular (necessário para a sessão do Sanctum). */
    protected function fromPanel(): static
    {
        return $this->withHeaders([
            'Origin' => 'http://localhost:4201',
            'Referer' => 'http://localhost:4201/',
            'Accept' => 'application/json',
        ]);
    }
}
