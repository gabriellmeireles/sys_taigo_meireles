<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * P-20 (decidido): mensagens de contato são excluídas automaticamente após 24 meses.
 * 730 dias passa a ser o padrão; os administradores continuam podendo excluir a qualquer momento.
 * A exclusão automática será feita por uma tarefa agendada diária (módulo Contact).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE site_settings MODIFY message_retention_days SMALLINT UNSIGNED NULL DEFAULT 730');
        DB::table('site_settings')->where('id', 1)->whereNull('message_retention_days')->update(['message_retention_days' => 730]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE site_settings MODIFY message_retention_days SMALLINT UNSIGNED NULL DEFAULT NULL');
    }
};
