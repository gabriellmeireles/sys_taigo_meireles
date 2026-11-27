<?php

namespace App\Domain\Localization;

/**
 * Fornecedor de tradução automática (P-28). Trocar de fornecedor = nova implementação
 * + TRANSLATION_PROVIDER no .env (ver config/translation.php).
 */
interface Translator
{
    /**
     * Traduz vários textos de uma vez, mantendo a ordem e as chaves.
     *
     * @param  array<string, string>  $texts  campo => texto no idioma de origem
     * @return array<string, string>          campo => texto traduzido
     *
     * @throws TranslationFailed
     */
    public function translate(array $texts, string $from, string $to): array;

    /** Nome exibido no painel (Integrações). */
    public function name(): string;
}
