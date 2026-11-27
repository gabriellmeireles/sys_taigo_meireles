<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Imagem de uma obra: ordem e principal (no máximo uma por obra, garantido no banco). */
class ArtworkImage extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id', 'primary_guard'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'sort_order' => 'integer'];
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }
}
