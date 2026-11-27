<?php

namespace App\Domain\Media;

use App\Jobs\GenerateImageVariants;
use App\Models\Admin;
use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Recebe arquivos de imagem: guarda o ORIGINAL sem alteração no disco privado
 * e agenda a geração das versões otimizadas (docs/05-arquitetura.md §6.8).
 *
 *   storage/app/private/media/originals/{aa}/{uuid}.{ext}   original (disco "local")
 *   storage/app/public/media/{aa}/{uuid}/w{largura}.{webp|jpg}  variantes (disco "public")
 */
class MediaLibrary
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/tiff'];

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/tiff' => 'tif',
    ];

    /** Imagem enviada pelo painel (o tipo é conferido pelo conteúdo, não pela extensão). */
    public function storeUploadedImage(UploadedFile $file, ?Admin $by = null, bool $processNow = false): MediaFile
    {
        return $this->storeImage($file->getRealPath(), $file->getClientOriginalName(), $by, $processNow);
    }

    /** Imagem a partir de um arquivo local (ex.: importação de obras de teste). */
    public function storeImage(string $path, string $originalName, ?Admin $by = null, bool $processNow = false): MediaFile
    {
        $mime = mime_content_type($path) ?: '';
        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => 'Formato não aceito. Envie JPG, PNG, TIFF ou WebP.',
            ]);
        }

        $uuid = (string) Str::uuid();
        $relative = sprintf('media/originals/%s/%s.%s', substr($uuid, 0, 2), $uuid, self::EXTENSIONS[$mime]);

        $stream = fopen($path, 'rb');
        Storage::disk('local')->put($relative, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        [$width, $height] = @getimagesize($path) ?: [null, null];

        $media = DB::transaction(fn () => MediaFile::create([
            'uuid' => $uuid,
            'kind' => 'image',
            'disk' => 'local',
            'original_path' => $relative,
            'original_filename' => mb_substr($originalName, 0, 255),
            'mime_type' => $mime,
            'size_bytes' => filesize($path),
            'width' => $width,
            'height' => $height,
            'checksum_sha256' => hash_file('sha256', $path),
            'processing_status' => MediaFile::STATUS_PENDING,
            'uploaded_by_admin_id' => $by?->getKey(),
        ]));

        $processNow
            ? GenerateImageVariants::dispatchSync($media->getKey())
            : GenerateImageVariants::dispatch($media->getKey());

        return $media->refresh();
    }

    /** Apaga original e variantes (só quando a mídia não está em uso). */
    public function delete(MediaFile $media): void
    {
        Storage::disk($media->disk)->delete($media->original_path);
        Storage::disk('public')->deleteDirectory(sprintf('media/%s/%s', substr($media->uuid, 0, 2), $media->uuid));
        $media->delete();
    }
}
