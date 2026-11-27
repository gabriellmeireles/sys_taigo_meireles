<?php

/*
 * Tradução automática (P-28). Fornecedor trocável:
 *   libretranslate  contêiner auto-hospedado (padrão)
 *   none            desliga a tradução automática
 */
return [

    'provider' => env('TRANSLATION_PROVIDER', 'libretranslate'),

    'libretranslate' => [
        'url' => env('LIBRETRANSLATE_URL', 'http://libretranslate:5000'),
        'api_key' => env('LIBRETRANSLATE_API_KEY'),
        'timeout' => (int) env('LIBRETRANSLATE_TIMEOUT', 30),
    ],

];
