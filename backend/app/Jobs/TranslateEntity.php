<?php

namespace App\Jobs;

use App\Domain\Localization\TranslatableTypes;
use App\Domain\Localization\TranslationFailed;
use App\Domain\Localization\TranslationSync;
use App\Domain\Localization\Translator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Tradução automática de um item para um idioma (fila; nunca na requisição do visitante). */
class TranslateEntity implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;
    public array $backoff = [15, 60, 300];
    public int $timeout = 120;

    public function __construct(public string $type, public int $id, public string $locale) {}

    public function handle(TranslationSync $sync, Translator $translator): void
    {
        $model = TranslatableTypes::get($this->type)['model']::find($this->id);
        if (! $model) {
            return;
        }

        try {
            $sync->translate($model, $this->locale);
            $this->integration('active', $translator->name(), null);
        } catch (TranslationFailed $e) {
            // Serviço fora do ar: registra para o painel e tenta de novo mais tarde,
            // sem afetar quem salvou a obra (o site mostra o português enquanto isso).
            $this->integration('error', null, mb_substr($e->getMessage(), 0, 500));
            $this->attempts() >= $this->tries
                ? $this->fail($e)
                : $this->release($this->backoff[$this->attempts() - 1] ?? 300);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::warning("Tradução automática falhou ({$this->type} #{$this->id} → {$this->locale}): ".$e?->getMessage());
        $this->integration('error', null, mb_substr((string) $e?->getMessage(), 0, 500));
    }

    private function integration(string $status, ?string $provider, ?string $error): void
    {
        $values = ['status' => $status, 'updated_at' => now()];
        if ($status === 'active') {
            $values += ['last_success_at' => now(), 'provider_label' => $provider];
        } else {
            $values += ['last_error_at' => now(), 'last_error' => $error];
        }
        DB::table('integration_states')->where('integration_key', 'translation')->update($values);
    }
}
