<?php

namespace Tests;

use App\Domain\Localization\Translator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakeTranslator;

abstract class TestCase extends BaseTestCase
{
    protected FakeTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        // Nenhum teste chama o LibreTranslate de verdade
        $this->translator = new FakeTranslator();
        $this->app->instance(Translator::class, $this->translator);
    }
}
