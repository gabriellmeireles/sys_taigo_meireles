<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * Administrador de teste, somente para desenvolvimento local.
 * Login do painel: admin@taigomeireles.test / senha-dev-12345
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        Admin::updateOrCreate(
            ['email' => 'admin@taigomeireles.test'],
            ['name' => 'Admin Dev', 'password_hash' => 'senha-dev-12345', 'is_active' => true],
        );
    }
}
