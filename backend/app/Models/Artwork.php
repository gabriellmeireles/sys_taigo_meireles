<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Obra. Dados não traduzíveis aqui; título, slug, descrição e SEO em artwork_translations.
 * Dimensões em cm, altura × largura × profundidade (P-23).
 */
class Artwork extends Model
{
    use HasTranslations;

    protected string $translationModel = ArtworkTranslation::class;
    protected string $translationForeignKey = 'artwork_id';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ArtworkStatus::class,
            'year' => 'integer',
            'height_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'depth_cm' => 'decimal:2',
            'price_brl' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'show_price' => 'boolean',
            'status_changed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function technique(): BelongsTo
    {
        return $this->belongsTo(Technique::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(YoutubeVideo::class, 'youtube_video_id');
    }

    /** Imagens na ordem definida no painel. */
    public function images(): HasMany
    {
        return $this->hasMany(ArtworkImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ArtworkImage::class)->where('is_primary', true);
    }

    public function title(?string $locale = null): ?string
    {
        return $this->translation($locale)?->title;
    }
}
