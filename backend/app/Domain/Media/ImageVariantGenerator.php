<?php

namespace App\Domain\Media;

use App\Models\MediaFile;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;

/**
 * Gera as versões otimizadas de uma imagem a partir do original:
 *  - larguras 480, 960, 1600 e 2400 px em WebP e JPEG (nunca maiores que o original);
 *  - "og" 1200×630 para compartilhamento (obra inteira, centralizada em fundo branco);
 *  - orientação da câmera aplicada; EXIF e GPS removidos; perfil de cor (ICC) preservado;
 *  - sem marca d'água (requisito).
 */
class ImageVariantGenerator
{
    /** variante => largura máxima */
    public const WIDTHS = ['thumb' => 480, 'small' => 960, 'large' => 1600, 'zoom' => 2400];
    public const OG_WIDTH = 1200;
    public const OG_HEIGHT = 630;
    public const QUALITY = 85;

    public function generate(MediaFile $media): void
    {
        $source = new Imagick();
        $source->readImageBlob(Storage::disk($media->disk)->get($media->original_path));
        $source->setIteratorIndex(0); // TIFF com várias páginas: usa a primeira

        $this->normalize($source);
        $width = $source->getImageWidth();
        $height = $source->getImageHeight();

        $dir = sprintf('media/%s/%s', substr($media->uuid, 0, 2), $media->uuid);
        $disk = Storage::disk('public');
        $disk->deleteDirectory($dir);
        $rows = [];
        $done = [];

        foreach (self::WIDTHS as $variant => $target) {
            $w = min($target, $width);
            if (isset($done[$w])) {
                continue; // original pequeno: não repete o mesmo tamanho
            }
            $done[$w] = true;

            $resized = clone $source;
            if ($w < $width) {
                $resized->resizeImage($w, 0, Imagick::FILTER_LANCZOS, 1);
            }

            foreach (['webp' => 'webp', 'jpeg' => 'jpg'] as $format => $ext) {
                $path = "{$dir}/w{$w}.{$ext}";
                $blob = $this->encode($resized, $format);
                $disk->put($path, $blob);
                $rows[] = [
                    'media_file_id' => $media->getKey(),
                    'variant' => $variant,
                    'format' => $format,
                    'path' => $path,
                    'width' => $resized->getImageWidth(),
                    'height' => $resized->getImageHeight(),
                    'size_bytes' => strlen($blob),
                    'quality' => self::QUALITY,
                ];
            }
            $resized->clear();
        }

        // Compartilhamento: a obra inteira (contain) sobre branco, 1200×630, JPEG
        $og = clone $source;
        $og->thumbnailImage(self::OG_WIDTH - 80, self::OG_HEIGHT - 80, true);
        $canvas = new Imagick();
        $canvas->newImage(self::OG_WIDTH, self::OG_HEIGHT, new ImagickPixel('#FFFFFF'));
        $canvas->compositeImage(
            $og,
            Imagick::COMPOSITE_OVER,
            intdiv(self::OG_WIDTH - $og->getImageWidth(), 2),
            intdiv(self::OG_HEIGHT - $og->getImageHeight(), 2),
        );
        $ogBlob = $this->encode($canvas, 'jpeg');
        $disk->put("{$dir}/og.jpg", $ogBlob);
        $rows[] = [
            'media_file_id' => $media->getKey(),
            'variant' => 'og',
            'format' => 'jpeg',
            'path' => "{$dir}/og.jpg",
            'width' => self::OG_WIDTH,
            'height' => self::OG_HEIGHT,
            'size_bytes' => strlen($ogBlob),
            'quality' => self::QUALITY,
        ];

        DB::transaction(function () use ($media, $rows, $width, $height, $source) {
            MediaVariant::where('media_file_id', $media->getKey())->delete();
            MediaVariant::insert(array_map(fn ($r) => $r + ['created_at' => now()], $rows));

            $media->forceFill([
                'width' => $width,
                'height' => $height,
                'dominant_color' => $this->dominantColor($source),
                'color_profile' => $this->profileName($source),
                'processing_status' => MediaFile::STATUS_READY,
                'processing_error' => null,
            ])->save();
        });

        $og->clear();
        $canvas->clear();
        $source->clear();
    }

    /** Orientação aplicada, CMYK/cinza para RGB, metadados removidos mantendo o ICC. */
    private function normalize(Imagick $im): void
    {
        $this->autoOrient($im);

        $icc = null;
        try {
            $icc = $im->getImageProfile('icc');
        } catch (\ImagickException) {
            // sem perfil embutido
        }

        if (in_array($im->getImageColorspace(), [Imagick::COLORSPACE_CMYK, Imagick::COLORSPACE_GRAY], true)) {
            $im->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            $icc = null; // o perfil CMYK não vale mais
        }

        $im->stripImage(); // remove EXIF, GPS, XMP...
        if ($icc) {
            $im->profileImage('icc', $icc); // ...mas mantém as cores fiéis ao original
        }
        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $im->setImageBackgroundColor('#FFFFFF');
    }

    private function autoOrient(Imagick $im): void
    {
        switch ($im->getImageOrientation()) {
            case Imagick::ORIENTATION_TOPRIGHT: $im->flopImage(); break;
            case Imagick::ORIENTATION_BOTTOMRIGHT: $im->rotateImage('#000', 180); break;
            case Imagick::ORIENTATION_BOTTOMLEFT: $im->flipImage(); break;
            case Imagick::ORIENTATION_LEFTTOP: $im->transposeImage(); break;
            case Imagick::ORIENTATION_RIGHTTOP: $im->rotateImage('#000', 90); break;
            case Imagick::ORIENTATION_RIGHTBOTTOM: $im->transverseImage(); break;
            case Imagick::ORIENTATION_LEFTBOTTOM: $im->rotateImage('#000', -90); break;
        }
        $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    private function encode(Imagick $im, string $format): string
    {
        $out = clone $im;
        $out->setImageFormat($format);
        $out->setImageCompressionQuality(self::QUALITY);
        if ($format === 'jpeg') {
            $out->setInterlaceScheme(Imagick::INTERLACE_PLANE); // JPEG progressivo
            $out->setSamplingFactors(['2x2', '1x1', '1x1']);
        }
        $blob = $out->getImagesBlob();
        $out->clear();

        return $blob;
    }

    private function dominantColor(Imagick $im): string
    {
        $px = clone $im;
        $px->resizeImage(1, 1, Imagick::FILTER_BOX, 1);
        $c = $px->getImagePixelColor(0, 0)->getColor();
        $px->clear();

        return sprintf('#%02X%02X%02X', $c['r'], $c['g'], $c['b']);
    }

    private function profileName(Imagick $im): ?string
    {
        try {
            $desc = $im->getImageProperty('icc:description');

            return $desc ? mb_substr($desc, 0, 100) : null;
        } catch (\ImagickException) {
            return null;
        }
    }
}
