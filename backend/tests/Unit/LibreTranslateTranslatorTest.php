<?php

namespace Tests\Unit;

use App\Domain\Localization\LibreTranslateTranslator;
use App\Domain\Localization\TranslationFailed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LibreTranslateTranslatorTest extends TestCase
{
    public function test_envia_os_textos_em_lote_com_codigos_do_libretranslate(): void
    {
        Http::fake(['lt.test/translate' => Http::response(['translatedText' => ['Tiger I', 'Oil on canvas.']])]);

        $out = (new LibreTranslateTranslator('http://lt.test'))->translate(
            ['title' => 'Tigre I', 'empty' => '  ', 'description' => 'Óleo sobre tela.'],
            'pt-BR', 'en',
        );

        $this->assertSame(['title' => 'Tiger I', 'description' => 'Oil on canvas.'], $out);
        Http::assertSent(fn (Request $r) => $r['source'] === 'pt' && $r['target'] === 'en'
            && $r['q'] === ['Tigre I', 'Óleo sobre tela.'] && $r['format'] === 'text');
    }

    public function test_erro_do_servico_vira_falha_de_traducao(): void
    {
        Http::fake(['lt.test/translate' => Http::response('erro', 500)]);

        $this->expectException(TranslationFailed::class);
        (new LibreTranslateTranslator('http://lt.test'))->translate(['t' => 'Olá'], 'pt-BR', 'es');
    }

    public function test_nada_para_traduzir_nao_chama_o_servico(): void
    {
        Http::fake();

        $this->assertSame([], (new LibreTranslateTranslator('http://lt.test'))->translate(['t' => ''], 'pt-BR', 'en'));
        Http::assertNothingSent();
    }
}
