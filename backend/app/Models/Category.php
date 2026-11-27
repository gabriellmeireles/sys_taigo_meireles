<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Taxonomy
{
    protected string $translationModel = CategoryTranslation::class;
    protected string $translationForeignKey = 'category_id';

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }
}
