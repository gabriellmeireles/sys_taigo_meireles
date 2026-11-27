<?php

namespace App\Domain\Localization;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** LibreTranslate auto-hospedado (contêiner "libretranslate"). */
class LibreTranslateTranslator implements Translator
{
    /** Códigos do site → códigos do LibreTranslate. */
    private const CODES = ['pt-BR' => 'pt', 'en' => 'en', 'es' => 'es'];

    public function __construct(
        private string $url,
        private ?string $apiKey = null,
        private int $timeout = 30,
    ) {}

    public function translate(array $texts, string $from, string $to): array
    {
        $texts = array_filter($texts, fn ($t) => is_string($t) && trim($t) !== '');
        if ($texts === []) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post(rtrim($this->url, '/').'/translate', array_filter([
                    'q' => array_values($texts),
                    'source' => self::CODES[$from] ?? $from,
                    'target' => self::CODES[$to] ?? $to,
                    'format' => 'text',
                    'api_key' => $this->apiKey,
                ]));
        } catch (ConnectionException $e) {
            throw new TranslationFailed('LibreTranslate indisponível: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new TranslationFailed("LibreTranslate respondeu {$response->status()}: ".mb_substr($response->body(), 0, 200));
        }

        $translated = $response->json('translatedText');
        if (! is_array($translated) || count($translated) !== count($texts)) {
            throw new TranslationFailed('Resposta inesperada do LibreTranslate.');
        }

        return array_combine(array_keys($texts), array_map('strval', $translated));
    }

    public function name(): string
    {
        return 'LibreTranslate';
    }
}
