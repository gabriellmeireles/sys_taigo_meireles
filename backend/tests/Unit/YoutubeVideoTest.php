<?php

namespace Tests\Unit;

use App\Models\YoutubeVideo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YoutubeVideoTest extends TestCase
{
    public static function links(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=30', 'dQw4w9WgXcQ'],
            ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
            ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            ['https://vimeo.com/123456', null],
            ['https://www.youtube.com/watch?v=curto', null],
            ['https://evil.test/watch?v=dQw4w9WgXcQ', null],
        ];
    }

    #[DataProvider('links')]
    public function test_extrai_o_id_do_video(string $url, ?string $expected): void
    {
        $this->assertSame($expected, YoutubeVideo::parseId($url));
    }
}
