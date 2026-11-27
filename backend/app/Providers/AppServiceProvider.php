<?php

namespace App\Providers;

use App\Domain\Localization\LibreTranslateTranslator;
use App\Domain\Localization\NullTranslator;
use App\Domain\Localization\Translator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fornecedor de tradução automática escolhido no .env (P-28)
        $this->app->bind(Translator::class, fn () => match (config('translation.provider')) {
            'libretranslate' => new LibreTranslateTranslator(
                config('translation.libretranslate.url'),
                config('translation.libretranslate.api_key'),
                config('translation.libretranslate.timeout'),
            ),
            default => new NullTranslator(),
        });
    }

    public function boot(): void
    {
        //
    }
}
