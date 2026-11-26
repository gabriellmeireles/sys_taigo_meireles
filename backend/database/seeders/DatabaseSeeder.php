<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados mínimos para desenvolvimento local.
     * Páginas, idiomas, site_settings e integration_states já vêm do DDL.
     */
    public function run(): void
    {
        $this->call([
            DevAdminSeeder::class,
            ContactSettingsSeeder::class,
        ]);
    }
}
