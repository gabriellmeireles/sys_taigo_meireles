<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base das tabelas *_translations: chave composta (entidade_id, locale_code),
 * só updated_at. Gravação pelo HasTranslations::saveTranslation (upsert).
 */
abstract class Translation extends Model
{
    public $incrementing = false;
    protected $primaryKey = null;
    public const CREATED_AT = null;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_outdated' => 'boolean'];
    }
}
