<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Media\MediaLibrary;
use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\MediaFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Upload de imagens (um arquivo por requisição, com progresso no painel). */
class MediaController extends Controller
{
    public function store(Request $request, MediaLibrary $library): JsonResponse
    {
        $maxKb = config('platform.media_max_upload_mb') * 1024;

        $request->validate([
            'file' => ['required', 'file', "max:{$maxKb}", 'mimetypes:'.implode(',', MediaLibrary::IMAGE_MIMES)],
        ], [
            'file.max' => 'A imagem passa do limite de '.config('platform.media_max_upload_mb').' MB.',
            'file.mimetypes' => 'Formato não aceito. Envie JPG, PNG, TIFF ou WebP.',
        ]);

        $media = $library->storeUploadedImage($request->file('file'), $request->user());

        return (new MediaResource($media->load('variants')))->response()->setStatusCode(201);
    }

    /** O painel consulta até o status ficar "ready" (ou "failed"). */
    public function show(MediaFile $media): MediaResource
    {
        return new MediaResource($media->load('variants'));
    }

    /** Download do arquivo original (privado; só para administradores). */
    public function original(MediaFile $media): StreamedResponse
    {
        return Storage::disk($media->disk)->download($media->original_path, $media->original_filename);
    }
}
