<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Versão otimizada (tamanho × formato) gerada a partir de um original. */
class MediaVariant extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['width' => 'integer', 'height' => 'integer', 'size_bytes' => 'integer'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
