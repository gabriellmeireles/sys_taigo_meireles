<?php

namespace App\Http\Requests\Admin;

use App\Models\ArtworkStatus;
use App\Models\Locale;
use App\Models\YoutubeVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Criação e edição de obra (tela 4.6 do painel). */
class SaveArtworkRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'year' => ['required', 'integer', 'between:1900,2100'],
            'height_cm' => ['nullable', 'numeric', 'gt:0', 'max:99999'],
            'width_cm' => ['nullable', 'numeric', 'gt:0', 'max:99999'],
            'depth_cm' => ['nullable', 'numeric', 'gt:0', 'max:99999'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'technique_id' => ['required', 'integer', 'exists:techniques,id'],
            'series_id' => ['required', 'integer', 'exists:series,id'], // P-06: série obrigatória
            'status' => ['required', Rule::enum(ArtworkStatus::class)],
            'show_price' => ['boolean'],
            // P-04: preços opcionais, obrigatórios quando "exibir preço" estiver ligado
            'price_brl' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'required_if:show_price,true,1'],
            'price_usd' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'required_if:show_price,true,1'],
            'sort_order' => ['sometimes', 'integer'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'images' => ['required', 'array', 'min:1', 'max:30'],
            'images.*.media_file_id' => ['required', 'integer', 'distinct', Rule::exists('media_files', 'id')->where('kind', 'image')],
            'images.*.is_primary' => ['boolean'],
            'translations' => ['required', 'array'],
            'translations.'.Locale::SOURCE.'.title' => ['required', 'string', 'max:200'],
        ];

        foreach (Locale::ALL as $locale) {
            $p = "translations.{$locale}.";
            $rules[$p.'title'] ??= ['nullable', 'string', 'max:200'];
            $rules[$p.'slug'] = ['nullable', 'string', 'max:220', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];
            $rules[$p.'description'] = ['nullable', 'string', 'max:20000'];
            $rules[$p.'dimensions_note'] = ['nullable', 'string', 'max:200'];
            $rules[$p.'seo_title'] = ['nullable', 'string', 'max:70'];
            $rules[$p.'seo_description'] = ['nullable', 'string', 'max:170'];
            $rules[$p.'og_title'] = ['nullable', 'string', 'max:100'];
            $rules[$p.'og_description'] = ['nullable', 'string', 'max:200'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $v) {
                $primaries = collect($this->input('images', []))->filter(fn ($i) => ! empty($i['is_primary']))->count();
                if ($primaries > 1) {
                    $v->errors()->add('images', 'Escolha apenas uma imagem principal.');
                }

                $url = trim((string) $this->input('video_url'));
                if ($url !== '' && YoutubeVideo::parseId($url) === null) {
                    $v->errors()->add('video_url', 'Informe um link válido do YouTube.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'translations.'.Locale::SOURCE.'.title.required' => 'Informe o título da obra.',
            'images.required' => 'Envie ao menos uma imagem da obra.',
            'images.min' => 'Envie ao menos uma imagem da obra.',
            'series_id.required' => 'Escolha a série.',
            'category_id.required' => 'Escolha a categoria.',
            'technique_id.required' => 'Escolha a técnica.',
            'price_brl.required_if' => 'Informe o preço em reais para exibir o preço no site.',
            'price_usd.required_if' => 'Informe o preço em dólares para exibir o preço no site.',
            '*.slug.regex' => 'Use só letras minúsculas, números e hífens.',
        ];
    }
}
