<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Configuração inicial de contato e das respostas automáticas (PT, EN, ES).
 * Os textos são ponto de partida e são editados no painel.
 */
class ContactSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('contact_settings')->updateOrInsert(['id' => 1], [
            'recipient_email' => 'contato@taigomeireles.com',
        ]);

        $replies = [
            'pt-BR' => [
                'subject' => 'Recebemos sua mensagem',
                'body' => "Olá, {nome}.\n\nObrigado pelo contato. Recebemos sua mensagem e responderemos em breve.\n\nAteliê Taigo Meireles",
            ],
            'en' => [
                'subject' => 'We received your message',
                'body' => "Hello, {nome}.\n\nThank you for reaching out. We received your message and will reply soon.\n\nTaigo Meireles Studio",
            ],
            'es' => [
                'subject' => 'Recibimos tu mensaje',
                'body' => "Hola, {nome}.\n\nGracias por escribirnos. Recibimos tu mensaje y responderemos pronto.\n\nTaller Taigo Meireles",
            ],
        ];

        foreach ($replies as $locale => $reply) {
            DB::table('auto_reply_templates')->updateOrInsert(['locale_code' => $locale], $reply + ['is_enabled' => 1]);
        }
    }
}
