<?php

namespace App\Models;

enum ArtworkStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
}
