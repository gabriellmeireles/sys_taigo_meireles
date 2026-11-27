<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Taxonomy
{
    protected $table = 'series';
    protected string $translationModel = SeriesTranslation::class;
    protected string $translationForeignKey = 'series_id';

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'cover_media_id');
    }
}
