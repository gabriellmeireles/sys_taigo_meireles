<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Troca a tabela própria admin_password_tokens pelas tabelas no formato do
 * Password Broker do Laravel (decisão da etapa 1):
 *  - password_reset_tokens: "esqueci minha senha" (link válido por 60 min)
 *  - admin_invite_tokens:   convite de novo administrador (link válido por 72 h)
 * O token é guardado com hash pelo próprio broker; o e-mail identifica o administrador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('admin_password_tokens');

        foreach (['password_reset_tokens', 'admin_invite_tokens'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->string('email', 191)->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_invite_tokens');
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('admin_password_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $t->enum('purpose', ['reset', 'invite']);
            $t->char('token_hash', 64)->unique('uq_apt_token');
            $t->dateTime('expires_at');
            $t->dateTime('used_at')->nullable();
            $t->dateTime('created_at')->useCurrent();
            $t->index(['admin_id', 'purpose'], 'ix_apt_admin');
        });
    }
};
