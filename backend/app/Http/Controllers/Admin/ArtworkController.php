<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\ArtworkWriter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveArtworkRequest;
use App\Http\Resources\ArtworkListResource;
use App\Http\Resources\ArtworkResource;
use App\Models\Artwork;
use App\Models\ArtworkStatus;
use App\Models\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Obras no painel (telas 4.5 e 4.6). */
class ArtworkController extends Controller
{
    public function __construct(private ArtworkWriter $writer) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ArtworkStatus::class)],
            'series_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'technique_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $artworks = Artwork::query()
            ->with(['translations', 'primaryImage.media.variants', 'series.translations', 'technique.translations'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('translations', fn ($t) => $t
                ->where('locale_code', Locale::SOURCE)
                ->where('title', 'like', '%'.addcslashes($term, '%_\\').'%')))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['series_id'] ?? null, fn ($q, $v) => $q->where('series_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['technique_id'] ?? null, fn ($q, $v) => $q->where('technique_id', $v))
            ->when($filters['year'] ?? null, fn ($q, $v) => $q->where('year', $v))
            ->orderBy('sort_order')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);

        return ArtworkListResource::collection($artworks);
    }

    public function show(Artwork $artwork): ArtworkResource
    {
        return new ArtworkResource($artwork->load(['translations', 'images.media.variants', 'video']));
    }

    public function store(SaveArtworkRequest $request): JsonResponse
    {
        $artwork = $this->writer->save(null, $request->validated(), $request->user());

        return (new ArtworkResource($artwork))->response()->setStatusCode(201);
    }

    public function update(SaveArtworkRequest $request, Artwork $artwork): ArtworkResource
    {
        return new ArtworkResource($this->writer->save($artwork, $request->validated(), $request->user()));
    }

    /** Troca rápida de status na lista. */
    public function updateStatus(Request $request, Artwork $artwork): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(ArtworkStatus::class)]]);
        $this->writer->setStatus($artwork, ArtworkStatus::from($data['status']));

        return response()->json(['data' => ['id' => $artwork->id, 'status' => $artwork->status->value]]);
    }

    public function destroy(Artwork $artwork): Response
    {
        $this->writer->delete($artwork);

        return response()->noContent();
    }
}
