<?php

namespace Tests\Fakes;

use App\Domain\Localization\TranslationFailed;
use App\Domain\Localization\Translator;

/** "Traduz" prefixando o idioma: "Tigre" → "[en] Tigre". Pode simular falha. */
class FakeTranslator implements Translator
{
    public bool $fail = false;

    /** @var array<int, array{from:string,to:string,texts:array}> */
    public array $calls = [];

    public function translate(array $texts, string $from, string $to): array
    {
        if ($this->fail) {
            throw new TranslationFailed('Falha simulada.');
        }
        $texts = array_filter($texts, fn ($t) => trim((string) $t) !== '');
        $this->calls[] = compact('from', 'to', 'texts');

        return array_map(fn ($t) => "[{$to}] {$t}", $texts);
    }

    public function name(): string
    {
        return 'Fake';
    }
}
