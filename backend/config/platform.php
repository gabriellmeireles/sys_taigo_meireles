<?php

/*
 * Configurações próprias da plataforma Taigo Meireles.
 */
return [

    // Endereços das aplicações Angular (usados nos links enviados por e-mail).
    'site_url' => rtrim((string) env('SITE_URL', 'http://localhost:4200'), '/'),
    'admin_url' => rtrim((string) env('ADMIN_URL', 'http://localhost:4201'), '/'),

    // Fuso usado no painel e nos cortes diários das métricas (P-17).
    'timezone' => env('SITE_TIMEZONE', 'America/Sao_Paulo'),

    // Limite de upload de imagens e PDFs, em MB (P-30).
    'media_max_upload_mb' => (int) env('MEDIA_MAX_UPLOAD_MB', 30),

    // Regras de senha dos administradores.
    'password_min_length' => 12,

    // Login: tentativas permitidas por e-mail + IP antes do bloqueio temporário.
    'login_max_attempts' => 5,
    'login_decay_seconds' => 60,

];
