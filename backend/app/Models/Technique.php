<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Technique extends Taxonomy
{
    protected string $translationModel = TechniqueTranslation::class;
    protected string $translationForeignKey = 'technique_id';

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }
}
