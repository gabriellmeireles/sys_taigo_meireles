<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Localization\TranslatableTypes;
use App\Domain\Localization\TranslationFailed;
use App\Domain\Localization\TranslationSync;
use App\Http\Controllers\Controller;
use App\Models\Locale;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Tela 4.14 — Central de traduções: estado por item e idioma, progresso, retradução. */
class TranslationController extends Controller
{
    private const STATES = ['auto', 'manual', 'outdated', 'missing'];

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_diff(Locale::ALL, [Locale::SOURCE]))],
            'type' => ['nullable', Rule::in(array_keys(TranslatableTypes::TYPES))],
            'state' => ['nullable', Rule::in(self::STATES)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $types = $data['type'] ?? null ? [$data['type']] : array_keys(TranslatableTypes::TYPES);
        $items = collect();
        foreach ($types as $type) {
            $items = $items->concat($this->rows($type, $data['locale'], $data['state'] ?? null, $data['q'] ?? null));
        }

        return response()->json(['data' => $items->sortBy([['type_order', 'asc'], ['title', 'asc']])->values()->map(
            fn ($i) => collect($i)->except('type_order')
        )]);
    }

    /** Contagem por idioma e estado (barra de progresso). */
    public function progress(): JsonResponse
    {
        $result = [];
        foreach (array_diff(Locale::ALL, [Locale::SOURCE]) as $locale) {
            $counts = array_fill_keys(self::STATES, 0);
            foreach (array_keys(TranslatableTypes::TYPES) as $type) {
                foreach ($this->rows($type, $locale, null, null) as $row) {
                    $counts[$row['state']]++;
                }
            }
            $total = array_sum($counts);
            $result[$locale] = $counts + [
                'total' => $total,
                'reviewed_percent' => $total ? (int) round($counts['manual'] * 100 / $total) : 0,
            ];
        }

        return response()->json(['data' => $result]);
    }

    /** "Gerar / retraduzir automaticamente" — sobrescreve o idioma escolhido. */
    public function auto(string $type, int $id, string $locale, TranslationSync $sync): JsonResponse
    {
        abort_unless(isset(TranslatableTypes::TYPES[$type]) && in_array($locale, $sync->targets(), true), 404);
        $model = TranslatableTypes::get($type)['model']::findOrFail($id);

        try {
            $sync->translate($model, $locale, force: true);
        } catch (TranslationFailed $e) {
            return response()->json([
                'message' => 'A tradução automática não respondeu agora. Tente de novo em instantes ou traduza à mão.',
            ], 503);
        }

        $row = DB::table(TranslatableTypes::get($type)['table'])
            ->where(TranslatableTypes::get($type)['fk'], $id)->where('locale_code', $locale)->first();

        return response()->json(['data' => (array) $row]);
    }

    /** @return array<int, array> */
    private function rows(string $type, string $locale, ?string $state, ?string $q): array
    {
        $def = TranslatableTypes::get($type);
        $title = $def['title'];

        $query = DB::table("{$def['table']} as s")
            ->leftJoin("{$def['table']} as t", function ($join) use ($def, $locale) {
                $join->on("t.{$def['fk']}", '=', "s.{$def['fk']}")->where('t.locale_code', $locale);
            })
            ->where('s.locale_code', Locale::SOURCE)
            ->when($q, fn (Builder $b) => $b->where("s.{$title}", 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->select([
                "s.{$def['fk']} as id",
                "s.{$title} as title",
                "t.{$title} as translated_title",
                't.translation_status',
                't.is_outdated',
                't.updated_at',
            ]);

        $order = array_search($type, array_keys(TranslatableTypes::TYPES), true);

        return $query->get()->map(function ($r) use ($type, $def, $order, $locale) {
            $rowState = match (true) {
                $r->translation_status === null => 'missing',
                (bool) $r->is_outdated => 'outdated',
                default => $r->translation_status,
            };

            return [
                'type' => $type,
                'type_label' => $def['label'],
                'type_order' => $order,
                'id' => $r->id,
                'title' => $r->title,
                'translated_title' => $r->translated_title,
                'locale' => $locale,
                'state' => $rowState,
                'auto' => $def['auto'],
                'updated_at' => $r->updated_at,
            ];
        })->when($state, fn ($c) => $c->where('state', $state))->values()->all();
    }
}
