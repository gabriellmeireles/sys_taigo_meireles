<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Idiomas do site: pt-BR (origem), en (fallback do navegador), es. */
class Locale extends Model
{
    public const SOURCE = 'pt-BR';
    public const ALL = ['pt-BR', 'en', 'es'];

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
