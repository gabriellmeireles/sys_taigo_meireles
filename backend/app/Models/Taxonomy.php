<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Base de categorias, técnicas e séries: nome e slug por idioma, ordem manual. */
abstract class Taxonomy extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    abstract public function artworks(): HasMany;

    public function name(?string $locale = null): ?string
    {
        return $this->translation($locale)?->name;
    }
}
