<?php

namespace App\Console\Commands;

use App\Domain\Localization\TranslatableTypes;
use App\Domain\Localization\TranslationSync;
use Illuminate\Console\Command;

/**
 * Agenda a tradução automática de tudo que ainda não tem tradução (ou cuja tradução
 * automática ficou para trás). Útil após importar conteúdo ou trocar de fornecedor.
 */
class SyncTranslations extends Command
{
    protected $signature = 'translations:sync {--type=artwork : artwork, series, technique ou category}';

    protected $description = 'Agenda traduções automáticas pendentes e marca revisadas desatualizadas';

    public function handle(TranslationSync $sync): int
    {
        $def = TranslatableTypes::get($this->option('type'));
        $count = 0;
        $def['model']::query()->orderBy('id')->each(function ($model) use ($sync, &$count) {
            $sync->sourceSaved($model);
            $count++;
        });

        $this->info("{$count} item(ns) verificados. As traduções automáticas seguem pela fila.");

        return self::SUCCESS;
    }
}
