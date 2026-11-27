<?php

namespace App\Domain\Localization;

use App\Domain\Catalog\Slugger;
use App\Jobs\TranslateEntity;
use App\Models\Admin;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo das traduções (docs/07-banco-de-dados.md §8.4):
 *  - salvou o português → idiomas sem tradução ou com tradução automática são
 *    (re)traduzidos na fila; traduções revisadas à mão NÃO são sobrescritas e
 *    ficam marcadas como desatualizadas;
 *  - alguém editou um idioma → vira "revisada" (manual) e deixa de estar desatualizada;
 *  - "Retraduzir automaticamente" sobrescreve de propósito, e volta a "automática".
 */
class TranslationSync
{
    public function __construct(private Translator $translator, private Slugger $slugger) {}

    /** Hash do texto de origem nos campos traduzíveis (detecta mudança). */
    public function sourceHash(Model $model): ?string
    {
        $def = TranslatableTypes::get(TranslatableTypes::typeOf($model));
        $source = $this->row($def, $model->getKey(), Locale::SOURCE);
        if (! $source) {
            return null;
        }

        $values = [];
        foreach ($def['fields'] as $field) {
            $values[$field] = trim((string) ($source->{$field} ?? ''));
        }

        return hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE));
    }

    /** Depois de salvar o idioma de origem. */
    public function sourceSaved(Model $model): void
    {
        $type = TranslatableTypes::typeOf($model);
        $def = TranslatableTypes::get($type);
        $hash = $this->sourceHash($model);
        if (! $hash) {
            return;
        }

        $this->update($def, $model->getKey(), Locale::SOURCE, ['source_hash' => $hash]);

        foreach ($this->targets() as $locale) {
            $row = $this->row($def, $model->getKey(), $locale);

            if ($row && $row->translation_status === 'manual') {
                if ($row->source_hash === null) {
                    // Revisada antes de existir o controle: passa a valer contra o texto atual
                    $this->update($def, $model->getKey(), $locale, ['source_hash' => $hash]);
                } elseif ($row->source_hash !== $hash && ! $row->is_outdated) {
                    $this->update($def, $model->getKey(), $locale, ['is_outdated' => true]);
                }
                continue;
            }

            if ($def['auto'] && (! $row || $row->source_hash !== $hash)) {
                TranslateEntity::dispatch($type, $model->getKey(), $locale)->afterCommit();
            }
        }
    }

    /** Depois que um administrador gravou um idioma de destino. */
    public function markReviewed(Model $model, string $locale, ?Admin $by = null): void
    {
        $def = TranslatableTypes::get(TranslatableTypes::typeOf($model));
        $values = [
            'translation_status' => 'manual',
            'is_outdated' => false,
            'source_hash' => $this->sourceHash($model),
        ];
        if ($def['audit']) {
            $values += ['reviewed_at' => now(), 'reviewed_by_admin_id' => $by?->getKey()];
        }
        $this->update($def, $model->getKey(), $locale, $values);
    }

    /**
     * Tradução automática de um idioma. Sem $force, não toca em tradução revisada.
     *
     * @throws TranslationFailed
     */
    public function translate(Model $model, string $locale, bool $force = false): bool
    {
        $def = TranslatableTypes::get(TranslatableTypes::typeOf($model));
        $source = $this->row($def, $model->getKey(), Locale::SOURCE);
        $current = $this->row($def, $model->getKey(), $locale);
        if (! $source || ($current?->translation_status === 'manual' && ! $force)) {
            return false;
        }

        $texts = [];
        foreach ($def['fields'] as $field) {
            $texts[$field] = (string) ($source->{$field} ?? '');
        }
        $translated = $this->translator->translate($texts, Locale::SOURCE, $locale);

        $values = [];
        foreach ($def['fields'] as $field) {
            $values[$field] = $translated[$field] ?? null;
        }
        $title = trim((string) ($values[$def['title']] ?? '')) ?: $source->{$def['title']};
        $values[$def['title']] = $title;
        $values['slug'] = $this->slugger->unique($def['table'], $def['fk'], $model->getKey(), $locale, $title, $def['slug_max']);
        $values['translation_status'] = 'auto';
        $values['is_outdated'] = false;
        $values['source_hash'] = $this->sourceHash($model);
        if ($def['audit']) {
            $values['translated_at'] = now();
            $values['reviewed_at'] = null;
            $values['reviewed_by_admin_id'] = null;
        }

        DB::table($def['table'])->upsert(
            [array_merge([$def['fk'] => $model->getKey(), 'locale_code' => $locale], $values, ['updated_at' => now()])],
            [$def['fk'], 'locale_code'],
            array_merge(array_keys($values), ['updated_at']),
        );

        return true;
    }

    /** @return string[] idiomas de destino */
    public function targets(): array
    {
        return array_values(array_diff(Locale::ALL, [Locale::SOURCE]));
    }

    private function row(array $def, int $id, string $locale): ?object
    {
        return DB::table($def['table'])->where($def['fk'], $id)->where('locale_code', $locale)->first();
    }

    private function update(array $def, int $id, string $locale, array $values): void
    {
        DB::table($def['table'])->where($def['fk'], $id)->where('locale_code', $locale)
            ->update($values + ['updated_at' => now()]);
    }
}
