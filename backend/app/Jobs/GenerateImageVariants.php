<?php

namespace App\Jobs;

use App\Domain\Media\ImageVariantGenerator;
use App\Models\MediaFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Gera as versões otimizadas de uma imagem enviada (fila "default"). */
class GenerateImageVariants implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;
    public array $backoff = [10, 60];

    public function __construct(public int $mediaFileId) {}

    public function handle(ImageVariantGenerator $generator): void
    {
        $media = MediaFile::find($this->mediaFileId);
        if (! $media || ! $media->isImage()) {
            return;
        }

        $media->forceFill(['processing_status' => MediaFile::STATUS_PROCESSING])->save();
        $generator->generate($media);
    }

    public function failed(?Throwable $e): void
    {
        MediaFile::whereKey($this->mediaFileId)->update([
            'processing_status' => MediaFile::STATUS_FAILED,
            'processing_error' => mb_substr((string) $e?->getMessage(), 0, 500),
        ]);
    }
}
