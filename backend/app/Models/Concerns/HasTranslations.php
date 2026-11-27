<?php

namespace App\Models\Concerns;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conteúdo traduzível em tabelas <entidade>_translations (docs/07-banco-de-dados.md §8.4).
 * A classe que usa o trait declara:
 *   protected string $translationModel = XTranslation::class;
 *   protected string $translationForeignKey = 'x_id';
 */
trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel, $this->translationForeignKey);
    }

    /** Tradução no idioma pedido; sem ela, a do idioma de origem (P-11). */
    public function translation(?string $locale = null): ?Model
    {
        $locale ??= Locale::SOURCE;
        $all = $this->relationLoaded('translations') ? $this->translations : $this->translations()->get();

        return $all->firstWhere('locale_code', $locale) ?? $all->firstWhere('locale_code', Locale::SOURCE);
    }

    public function scopeWithTranslations(Builder $query): Builder
    {
        return $query->with('translations');
    }

    /**
     * Grava (cria ou atualiza) a tradução de um idioma.
     * $status: 'manual' quando escrita por um administrador; 'auto' quando gerada.
     */
    public function saveTranslation(string $locale, array $values, string $status = 'manual'): void
    {
        $model = $this->translationModel;
        $keys = [$this->translationForeignKey => $this->getKey(), 'locale_code' => $locale];

        $model::query()->upsert(
            [array_merge($keys, $values, ['translation_status' => $status, 'is_outdated' => false])],
            array_keys($keys),
            array_merge(array_keys($values), ['translation_status', 'is_outdated']),
        );

        $this->unsetRelation('translations');
    }
}
