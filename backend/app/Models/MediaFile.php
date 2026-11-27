<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Arquivo ORIGINAL enviado (imagem ou PDF), guardado sem alteração e de forma privada.
 * As versões otimizadas servidas ao site ficam em media_variants.
 */
class MediaFile extends Model
{
    use HasTranslations;

    protected string $translationModel = MediaFileTranslation::class;
    protected string $translationForeignKey = 'media_file_id';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'focal_x' => 'float',
            'focal_y' => 'float',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }
}
