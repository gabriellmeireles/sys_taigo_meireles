<?php

namespace App\Domain\Localization;

/**
 * Sem fornecedor configurado: nada é traduzido automaticamente e o site mostra
 * o idioma de origem até alguém traduzir à mão (P-11).
 */
class NullTranslator implements Translator
{
    public function translate(array $texts, string $from, string $to): array
    {
        throw new TranslationFailed('Tradução automática desativada (TRANSLATION_PROVIDER=none).');
    }

    public function name(): string
    {
        return 'Nenhum';
    }
}
