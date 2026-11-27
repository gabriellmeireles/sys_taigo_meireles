<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Slugger;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaxonomyResource;
use App\Models\Locale;
use App\Models\Taxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD comum de categorias, técnicas e séries (tela 4.7).
 * Nome por idioma (PT obrigatório), slug gerado do nome, ordem manual.
 */
abstract class TaxonomyController extends Controller
{
    /** @return class-string<Taxonomy> */
    abstract protected function model(): string;

    /** Ex.: "categoria", usado nas mensagens. */
    abstract protected function noun(): string;

    abstract protected function translationTable(): string;

    abstract protected function foreignKey(): string;

    public function __construct(private Slugger $slugger) {}

    public function index(): AnonymousResourceCollection
    {
        $model = $this->model();

        return TaxonomyResource::collection(
            $model::query()->with('translations')->withCount('artworks')->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->model();
        $item = DB::transaction(function () use ($request, $model) {
            $data = $this->validated($request);
            $item = $model::create(['sort_order' => $data['sort_order'] ?? ($model::max('sort_order') + 1)]);
            $this->saveNames($item, $data['translations']);

            return $item;
        });

        return (new TaxonomyResource($item->load('translations')->loadCount('artworks')))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): TaxonomyResource
    {
        $item = $this->find($id);
        DB::transaction(function () use ($request, $item) {
            $data = $this->validated($request);
            if (isset($data['sort_order'])) {
                $item->update(['sort_order' => $data['sort_order']]);
            }
            $this->saveNames($item, $data['translations']);
        });

        return new TaxonomyResource($item->load('translations')->loadCount('artworks'));
    }

    public function destroy(int $id): Response
    {
        $item = $this->find($id);
        $count = $item->artworks()->count();
        if ($count > 0) {
            throw ValidationException::withMessages([
                'id' => "{$count} obra(s) usam esta {$this->noun()}. Reatribua antes de excluir.",
            ]);
        }
        $item->delete();

        return response()->noContent();
    }

    /** Nova ordem: { "ids": [3, 1, 2] }. */
    public function reorder(Request $request): Response
    {
        $model = $this->model();
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct']])['ids'];

        DB::transaction(function () use ($ids, $model) {
            foreach ($ids as $position => $id) {
                $model::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return response()->noContent();
    }

    private function find(int $id): Taxonomy
    {
        return $this->model()::query()->findOrFail($id);
    }

    private function validated(Request $request): array
    {
        $rules = [
            'sort_order' => ['sometimes', 'integer'],
            'translations' => ['required', 'array'],
            'translations.'.Locale::SOURCE.'.name' => ['required', 'string', 'max:160'],
        ];
        foreach (Locale::ALL as $locale) {
            $rules["translations.{$locale}.name"] ??= ['nullable', 'string', 'max:160'];
        }

        return $request->validate($rules, [
            'translations.'.Locale::SOURCE.'.name.required' => 'Informe o nome em português.',
        ]);
    }

    private function saveNames(Taxonomy $item, array $translations): void
    {
        foreach ($translations as $locale => $values) {
            $name = trim((string) ($values['name'] ?? ''));
            if ($name === '' || ! in_array($locale, Locale::ALL, true)) {
                continue;
            }
            $slug = $this->slugger->unique($this->translationTable(), $this->foreignKey(), $item->getKey(), $locale, $name, 180);
            $item->saveTranslation($locale, ['name' => $name, 'slug' => $slug], 'manual');
        }
    }
}
