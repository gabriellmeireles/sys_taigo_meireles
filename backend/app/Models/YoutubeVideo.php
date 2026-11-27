<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class YoutubeVideo extends Model
{
    use HasTranslations;

    protected string $translationModel = YoutubeVideoTranslation::class;
    protected string $translationForeignKey = 'youtube_video_id';

    protected $guarded = ['id'];

    /**
     * Extrai o ID de 11 caracteres de links do YouTube
     * (watch?v=, youtu.be/, embed/, shorts/, live/). Devolve null se não reconhecer.
     */
    public static function parseId(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }

        $pattern = '~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~i';

        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }
}
